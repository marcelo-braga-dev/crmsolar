<?php

namespace Database\Seeders\Support;

use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\Concessionaria;
use App\Models\Contrato;
use App\Models\FunilEtapa;
use App\Models\Kit;
use App\Models\Lead;
use App\Models\MargemEstado;
use App\Models\MargemPrincipal;
use App\Models\MotivoPerda;
use App\Models\Orcamento;
use App\Models\OrcamentoAprovacao;
use App\Models\OrcamentoHistorico;
use App\Models\OrcamentoVistoria;
use App\Models\ParamDimensionamento;
use App\Models\PropostaServico;
use App\Models\User;
use App\Models\VisitaTecnica;
use App\Services\DimensionamentoService;
use App\Services\Funil\FunilService;
use App\Services\Funil\MovimentoInvalido;
use App\Services\GrupoTarifarioService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Conta a história comercial da empresa de demonstração: equipe contratada ao longo do
 * tempo (e um consultor que sai), clientes e leads crescendo mês a mês com sazonalidade, e
 * cada orçamento percorrendo o funil pelo FunilService — atendimento, contatos, visita
 * técnica, aprovação (às vezes reprovação), contrato, instalação e pós-venda — ou sendo
 * perdido com motivo e, às vezes, reativado. Tudo agendado na Agenda e executado na data
 * em que aconteceu; o que cairia no futuro fica pendente, formando a fila de trabalho de hoje.
 */
final class SimuladorComercial
{
    private readonly Agenda $agenda;

    private readonly FunilService $funil;

    private readonly FabricaOrcamento $fabrica;

    /** @var array<string, User> login => usuário (criados durante a simulação) */
    private array $usuarios = [];

    /** @var array<string, CidadeEstado> "UF|Cidade" */
    private array $cidades = [];

    /** @var Collection<int, FunilEtapa> etapas abertas na ordem */
    private Collection $etapas;

    /** @var array<string, MotivoPerda> */
    private array $motivos;

    /** @var array<int, array<string, mixed>> plano do ciclo de cada orçamento */
    private array $planos = [];

    /** @var array<string, true> */
    private array $nomesUsados = [];

    private int $sequencia = 0;

    private string $senha;

    /** @var array<string, int> */
    public array $contagem = ['clientes' => 0, 'leads' => 0, 'orcamentos' => 0, 'ganhos' => 0, 'perdidos' => 0, 'reativados' => 0,
        'reprovados' => 0, 'contratos' => 0, 'visitas' => 0, 'propostas' => 0, 'ampliacoes' => 0, 'recusas_do_funil' => 0];

    /** @param  float  $escala  multiplica o volume de clientes e leads (1 = base de marketing; os testes usam menos) */
    public function __construct(private readonly CarbonImmutable $inicio, CarbonImmutable $agora, private readonly float $escala = 1.0)
    {
        $this->agenda = new Agenda($agora);
        $this->funil = app(FunilService::class);
        $this->fabrica = new FabricaOrcamento(app(DimensionamentoService::class), app(GrupoTarifarioService::class));
        $this->etapas = FunilEtapa::where('tipo', FunilEtapa::ABERTA)->where('ativa', true)->orderBy('ordem')->get();
        $this->motivos = MotivoPerda::all()->keyBy('nome')->all();
        $this->senha = Hash::make(CatalogoDemo::SENHA_PADRAO);

        foreach (CatalogoDemo::CIDADES as $uf => $cidades) {
            foreach (CidadeEstado::where('sigla', $uf)->whereIn('cidade', array_keys($cidades))->get() as $c) {
                $this->cidades["{$uf}|{$c->cidade}"] = $c;
            }
        }
    }

    public function executar(): void
    {
        $this->agendarEquipe();
        $this->agendarMudancasAdministrativas();
        $this->agendarClientes();
        $this->agendarLeadsNaoConvertidos();

        $this->agenda->executar();
    }

    /** @return array{executados: int, futuros: int} */
    public function eventos(): array
    {
        return $this->agenda->contagem();
    }

    /** @return array<string, User> */
    public function usuarios(): array
    {
        return $this->usuarios;
    }

    // ── Equipe ────────────────────────────────────────────────────────────

    private function agendarEquipe(): void
    {
        foreach (CatalogoDemo::EQUIPE as $i => $membro) {
            // O primeiro admin já existe antes da operação começar; os demais são cadastrados por ele.
            $quando = $i === 0 ? $this->inicio->subDays(6)->setTime(12, 0) : Agenda::expediente($this->dataDoMes($membro['entrada'], Sorteio::entre(1, 5)));
            $this->agenda->em($quando, $i === 0 ? null : fn () => $this->usuarios['admin'] ?? null, function () use ($membro, $i) {
                $this->usuarios[$membro['login']] = User::create([
                    'name' => $membro['nome'],
                    'email' => "{$membro['login']}@".CatalogoDemo::DOMINIO_EQUIPE,
                    'password' => $this->senha,
                    'tipo' => $membro['tipo'],
                    'status' => true,
                    'cpf' => Ficticio::cpf(900 + $i),
                    'celular' => Ficticio::telefone(900 + $i),
                    'comissao_percentual' => $membro['comissao'] ?? 0,
                ]);
            });

            if (isset($membro['saida'])) {
                $this->agenda->em(Agenda::expediente($this->dataDoMes($membro['saida'], 12)), fn () => $this->admin(), fn () => $this->desligar($membro['login']));
            }
        }
    }

    /**
     * Consultor sai da empresa: é desativado e quem assume a região herda as negociações
     * em andamento (reatribuídas pelo funil, com a comissão do novo responsável) e os clientes delas.
     */
    private function desligar(string $login): void
    {
        $saindo = $this->usuarios[$login];
        $substituto = $this->substitutoDe($saindo);
        $saindo->update(['status' => false]);

        foreach (Orcamento::where('consultor_id', $saindo->id)->whereNull('perdido_em')->whereIn('status', Orcamento::STATUS_EM_NEGOCIACAO)->get() as $orcamento) {
            $this->tentar(fn () => $this->funil->reatribuir($orcamento, $substituto, $this->admin()));
            Cliente::find($orcamento->cliente_id)?->update(['consultor_id' => $substituto->id]);
        }
    }

    private function substitutoDe(User $consultor): User
    {
        $cidades = collect(CatalogoDemo::EQUIPE)->firstWhere('login', Str::before($consultor->email, '@'))['cidades'] ?? [];
        $sucessor = collect(CatalogoDemo::EQUIPE)
            ->filter(fn ($m) => $m['tipo'] === 'consultor' && ! isset($m['saida']) && isset($this->usuarios[$m['login']])
                && array_intersect(array_merge(...array_values($m['cidades'])), array_merge(...array_values($cidades))))
            ->first();

        return $this->usuarios[$sucessor['login'] ?? 'consultor'];
    }

    /** Admin que executa a ação: o diretor ou, depois de contratada, a gerente comercial. */
    private function admin(): User
    {
        return isset($this->usuarios['gerente']) && Sorteio::chance(0.55) ? $this->usuarios['gerente'] : $this->usuarios['admin'];
    }

    /** Dono atual do orçamento, ou quem assumiu a carteira se ele saiu da empresa. */
    private function dono(int $orcamentoId): ?User
    {
        $consultor = User::find(Orcamento::withTrashed()->whereKey($orcamentoId)->value('consultor_id'));

        return $consultor && ! $consultor->status ? $this->substitutoDe($consultor) : $consultor;
    }

    // ── Mudanças administrativas (aparecem na Auditoria) ─────────────────

    private function agendarMudancasAdministrativas(): void
    {
        $reajuste = function (string $nome, float $fator) {
            $c = Concessionaria::where('nome', $nome)->first();
            $c?->update([
                'tarifa_convencional' => round((float) $c->tarifa_convencional * $fator, 5),
                'tarifa_ponta' => round((float) $c->tarifa_ponta * $fator, 5),
                'tarifa_fora_ponta' => round((float) $c->tarifa_fora_ponta * $fator, 5),
            ]);
        };

        $mudancas = [
            // Reajustes tarifários anuais das concessionárias da região.
            [3, 9, 'admin', fn () => $reajuste('CPFL Paulista', 1.062)],
            [6, 14, 'admin', fn () => $reajuste('CEMIG', 1.048)],
            [9, 3, 'admin', fn () => $reajuste('Copel', 1.071)],
            [12, 20, 'admin', fn () => $reajuste('Enel Goiás', 1.055)],
            [15, 4, 'admin', fn () => $reajuste('CPFL Piratininga', 1.039)],
            // Prazos do funil ajustados ao ciclo real de venda solar logo no começo da operação.
            [1, 10, 'admin', function () {
                foreach (['Primeiro contato' => 3, 'Proposta apresentada' => 7, 'Visita técnica' => 10, 'Negociação' => 12, 'Financiamento' => 20, 'Fechamento' => 7, 'Em aprovação' => 3] as $nome => $sla) {
                    FunilEtapa::where('nome', $nome)->first()?->update(['sla_dias' => $sla]);
                }
            }],
            // Parâmetros e margens ajustados com a experiência da operação.
            [5, 18, 'admin', fn () => ParamDimensionamento::where('chave', 'perda_leste_oeste')->first()?->update(['valor' => '11'])],
            [8, 6, 'gerente', fn () => MargemPrincipal::where('nome', 'Residencial')->first()?->update(['margem' => 26])],
            [11, 10, 'gerente', fn () => MargemEstado::where('estado', 'GO')->first()?->update(['margem' => 3.0])],
            [14, 2, 'gerente', fn () => MargemPrincipal::where('nome', 'Comercial pequeno')->first()?->update(['margem' => 25])],
            // Promoção do melhor consultor: comissão maior vale para os orçamentos seguintes.
            [9, 1, 'admin', fn () => $this->usuarios['consultor']->update(['comissao_percentual' => 5.5])],
            [13, 8, 'gerente', fn () => FunilEtapa::where('nome', 'Financiamento')->first()?->update(['sla_dias' => 18])],
            [13, 9, 'gerente', fn () => MotivoPerda::firstOrCreate(['nome' => 'Aguardando aprovação do condomínio'], ['reativavel' => true, 'reativar_apos_dias' => 45, 'ordem' => 8, 'ativo' => true])],
        ];

        foreach ($mudancas as [$mes, $dia, $login, $acao]) {
            $this->agenda->em(Agenda::expediente($this->dataDoMes($mes, $dia)), fn () => $this->usuarios[$login] ?? $this->usuarios['admin'], fn () => $acao());
        }

        // Correções manuais de preço de alguns kits (o resto vem da sincronização Edeltec).
        foreach ([[10, 15], [12, 7], [14, 22], [15, 9]] as [$mes, $dia]) {
            $this->agenda->em(Agenda::expediente($this->dataDoMes($mes, $dia)), fn () => $this->admin(), function () {
                $kits = Kit::where('ativo', true)->orderBy('id')->get();
                for ($i = 0; $i < 3; $i++) {
                    $kit = Sorteio::escolher($kits->all());
                    $kit->update(['preco_custo' => round((float) $kit->preco_custo * Sorteio::decimal(0.94, 1.06, 3), 2)]);
                }
            });
        }
    }

    // ── Clientes (com ou sem lead de origem) ──────────────────────────────

    private function agendarClientes(): void
    {
        $agora = $this->agenda->agora();
        $ultimoMes = (int) round($this->inicio->diffInMonths($agora->startOfMonth()));

        for ($mes = 0; $mes <= $ultimoMes; $mes++) {
            $inicioMes = $this->dataDoMes($mes, 1);
            $diasNoMes = $inicioMes->daysInMonth;
            $fracao = $mes === $ultimoMes ? ($agora->day - 1) / $diasNoMes : 1.0;

            foreach (CatalogoDemo::EQUIPE as $membro) {
                if ($membro['tipo'] !== 'consultor' || $membro['entrada'] > $mes || (isset($membro['saida']) && $mes >= $membro['saida'])) {
                    continue;
                }

                // Carteira cresce com o tempo de casa; sazonalidade e ruído deixam cada mês diferente.
                $mesesDeCasa = $mes - $membro['entrada'];
                $esperado = min(3.2 + 0.65 * $mesesDeCasa, 11) * $membro['ritmo'] * CatalogoDemo::SAZONALIDADE[$inicioMes->month];
                $esperado = Sorteio::ruido($esperado, 0.12) * $fracao * ($mesesDeCasa === 0 ? 0.6 : 1) * $this->escala;
                $quantidade = (int) floor($esperado) + (Sorteio::chance($esperado - floor($esperado)) ? 1 : 0);

                for ($i = 0; $i < $quantidade; $i++) {
                    $dia = Sorteio::entre($mesesDeCasa === 0 ? 8 : 1, max(1, (int) floor($diasNoMes * $fracao)));
                    $quando = Agenda::expediente($this->dataDoMes($mes, $dia));
                    if ($quando->greaterThan($agora)) {
                        continue;
                    }
                    $this->agendarCliente($membro, $quando);
                }
            }
        }
    }

    /** @param  array<string, mixed>  $membro */
    private function agendarCliente(array $membro, CarbonImmutable $quando): void
    {
        $uf = (string) array_key_first($membro['cidades']);
        $nomeCidade = Sorteio::escolher($membro['cidades'][$uf]);
        $pessoa = $this->pessoa($uf, $nomeCidade);
        $comLead = Sorteio::chance(0.55);
        $leadId = null;

        if ($comLead) {
            $chegada = Agenda::depois($quando->subDays(6), 0, 4);
            $chegada = $chegada->lessThan($quando) ? $chegada : $quando->subHours(Sorteio::entre(3, 20));
            $this->agendarLead($pessoa, $membro['login'], $chegada, $quando, function (int $id) use (&$leadId) {
                $leadId = $id;
            });
        }

        $this->agenda->em($quando, fn () => $this->usuarios[$membro['login']], function (CarbonImmutable $t) use ($pessoa, $membro, &$leadId) {
            $consultor = $this->usuarios[$membro['login']];
            $cliente = $this->criarCliente($pessoa, $consultor);

            if ($leadId) {
                Lead::find($leadId)?->update(['status' => 'convertido', 'consultor_id' => $consultor->id, 'anotacoes' => "Convertido em cliente #{$cliente->id} por {$consultor->name}."]);
            }

            if (Sorteio::chance(0.04)) {
                $cliente->update(['anotacoes' => trim(($cliente->anotacoes ?? '').' Aguardando as últimas contas de luz para dimensionar.')]);

                return;
            }

            // Orçamento no mesmo dia (às vezes em até 3 dias, depois de levantar a conta de luz).
            $quandoOrcamento = Sorteio::chance(0.8) ? $t->addMinutes(Sorteio::entre(20, 90)) : Agenda::depois($t, 1, 3);
            $this->agenda->em($quandoOrcamento, fn () => $this->usuarios[$membro['login']], fn (CarbonImmutable $t2) => $this->novoOrcamento($cliente->id, $pessoa['perfil'], $t2));
        });
    }

    /**
     * Pessoa física ou jurídica fictícia com o perfil de consumo coerente com o segmento.
     *
     * @return array<string, mixed>
     */
    private function pessoa(string $uf, string $cidade): array
    {
        $rural = Sorteio::chance(CatalogoDemo::PESO_RURAL[$uf]);
        $pj = ! $rural && Sorteio::chance(0.3);
        $concessionaria = CatalogoDemo::CIDADES[$uf][$cidade][1];
        $feminino = Sorteio::chance(0.48);
        $contato = $this->nomeUnico($feminino);

        if ($rural) {
            [$tipoInstalacao, $rotulo, $min, $max, $bomba] = Sorteio::escolher(CatalogoDemo::PERFIS_RURAIS);
            $consumo = round(Sorteio::assimetrico($min, $max) / 10) * 10;
            $consumoBomba = $bomba ? round($consumo * Sorteio::decimal(0.3, 0.45) / 10) * 10 : null;
            $propriedade = $rotulo.' '.Sorteio::escolher(CatalogoDemo::FANTASIAS);
            $perfil = ['grupo' => 'B2', 'consumo' => $consumo + ($consumoBomba ?? 0), 'fases' => Sorteio::chance(0.7) ? 'trifasico' : 'bifasico',
                'objetivo' => 100, 'tipo_instalacao' => $tipoInstalacao, 'bombeamento' => $bomba, 'consumo_bombeamento' => $consumoBomba,
                'categorias' => $bomba && Sorteio::chance(0.3) ? ['bomba'] : []];

            return ['tipo' => 'pf', 'nome' => $contato, 'feminino' => $feminino, 'uf' => $uf, 'cidade' => $cidade, 'contato' => $contato,
                'anotacoes' => "Propriedade: {$propriedade}.", 'perfil' => $perfil + ['concessionaria' => $concessionaria]];
        }

        if ($pj) {
            $industrial = Sorteio::chance(0.07);
            $segmento = Sorteio::escolher($industrial ? CatalogoDemo::SEGMENTOS_A : CatalogoDemo::SEGMENTOS_B3);
            $razao = $this->razaoUnica($segmento[0]);

            if ($industrial) {
                [, $min, $max, $demanda, $grupo] = $segmento;
                $total = round(Sorteio::assimetrico($min, $max) / 100) * 100;
                $ponta = round($total * Sorteio::decimal(0.08, 0.13) / 10) * 10;
                $azul = Sorteio::chance(0.2);
                $demandaPonta = round(Sorteio::ruido($demanda, 0.2));
                $perfil = ['grupo' => $grupo, 'demanda' => true, 'consumo' => $total, 'consumo_ponta' => $ponta, 'consumo_fora_ponta' => $total - $ponta,
                    'demanda_ponta' => $demandaPonta, 'demanda_fp' => $azul ? round($demandaPonta * 1.12) : null,
                    'modalidade' => $azul ? 'THS_AZUL' : 'THS_VERDE', 'autoconsumo' => Sorteio::entre(85, 100)];
            } else {
                [, $min, $max, $horas] = $segmento;
                $consumo = round(Sorteio::assimetrico($min, $max) / 10) * 10;
                $perfil = ['grupo' => 'B3', 'consumo' => $consumo, 'fases' => Sorteio::chance(0.75) ? 'trifasico' : 'bifasico', 'objetivo' => 100,
                    'horario' => $horas, 'autoconsumo' => Sorteio::entre(45, 85)];
            }

            return ['tipo' => 'pj', 'razao' => $razao, 'feminino' => true, 'uf' => $uf, 'cidade' => $cidade, 'contato' => $contato,
                'anotacoes' => "Contato: {$contato}.", 'perfil' => $perfil + ['concessionaria' => $concessionaria]];
        }

        $consumo = round(Sorteio::assimetrico(220, 1300) / 10) * 10;
        $fases = $consumo < 300 && Sorteio::chance(0.5) ? 'monofasico' : ($consumo > 700 && Sorteio::chance(0.6) ? 'trifasico' : 'bifasico');
        $perfil = ['grupo' => 'B1', 'consumo' => $consumo, 'fases' => $fases,
            'objetivo' => (int) Sorteio::ponderado([100 => 85, 75 => 12, 50 => 3]), 'categorias' => Sorteio::chance(0.07) ? ['hibrido'] : []];

        return ['tipo' => 'pf', 'nome' => $contato, 'feminino' => $feminino, 'uf' => $uf, 'cidade' => $cidade, 'contato' => $contato,
            'anotacoes' => Sorteio::chance(0.3) ? Sorteio::escolher(['Quer instalar antes do verão.', 'Planeja comprar carro elétrico.', 'Casa nova, mudança em 2 meses.', 'Indicado por vizinho que já tem usina.']) : null,
            'perfil' => $perfil + ['concessionaria' => $concessionaria]];
    }

    /** @param  array<string, mixed>  $pessoa */
    private function criarCliente(array $pessoa, User $consultor): Cliente
    {
        $n = ++$this->sequencia;
        $cidade = $this->cidades["{$pessoa['uf']}|{$pessoa['cidade']}"];
        $prefixoCep = CatalogoDemo::CIDADES[$pessoa['uf']][$pessoa['cidade']][0];
        $pf = $pessoa['tipo'] === 'pf';

        $cliente = Cliente::create([
            'consultor_id' => $consultor->id,
            'cidade_id' => $cidade->id,
            'tipo_pessoa' => $pessoa['tipo'],
            'nome' => $pf ? $pessoa['nome'] : null,
            'razao_social' => $pf ? null : $pessoa['razao'],
            'cpf' => $pf ? Ficticio::cpf($n) : null,
            'cnpj' => $pf ? null : Ficticio::cnpj($n),
            'rg' => $pf ? Ficticio::rg($n) : null,
            'data_nascimento' => $pf ? CarbonImmutable::create(Sorteio::entre(1950, 1996), Sorteio::entre(1, 12), Sorteio::entre(1, 28)) : null,
            'email' => Ficticio::email($pf ? $pessoa['nome'] : $pessoa['razao'], $n, CatalogoDemo::DOMINIO_CLIENTES),
            'telefone' => Sorteio::chance(0.4) ? Ficticio::telefone($n, 1) : null,
            'celular' => Ficticio::telefone($n),
            'cep' => sprintf('%s%02d-%03d', $prefixoCep, Sorteio::entre(0, 99), Sorteio::entre(0, 999)),
            'rua' => Sorteio::escolher(CatalogoDemo::RUAS),
            'numero' => (string) Sorteio::entre(12, 2900),
            'complemento' => Sorteio::chance(0.15) ? 'Casa '.Sorteio::entre(1, 4) : null,
            'bairro' => Sorteio::escolher(CatalogoDemo::BAIRROS),
            'status' => 'novo',
            'anotacoes' => $pessoa['anotacoes'],
        ]);
        $this->contagem['clientes']++;

        return $cliente;
    }

    // ── Leads ─────────────────────────────────────────────────────────────

    /**
     * Lead do formulário/API (sem autor) → admin encaminha ao consultor → consultor contata.
     * Convertido quando vira cliente (no evento do cliente); os demais terminam perdidos ou seguem na fila.
     *
     * @param  array<string, mixed>  $pessoa
     * @param  callable(int): void  $aoCriar
     */
    private function agendarLead(array $pessoa, string $login, CarbonImmutable $quando, ?CarbonImmutable $conversao, callable $aoCriar): void
    {
        $this->agenda->em($quando, null, function (CarbonImmutable $t) use ($pessoa, $login, $conversao, $aoCriar) {
            $origem = CatalogoDemo::ORIGENS_LEAD[(int) Sorteio::ponderado(CatalogoDemo::PESO_ORIGENS)];
            $lead = Lead::create([
                'nome' => $pessoa['contato'],
                'email' => Ficticio::email($pessoa['contato'], 5000 + $this->contagem['leads'], CatalogoDemo::DOMINIO_CLIENTES),
                'telefone' => Ficticio::telefone(5000 + $this->contagem['leads']),
                'cidade' => $pessoa['cidade'],
                'estado' => $pessoa['uf'],
                'consumo_mensal' => $pessoa['perfil']['consumo'],
                'origem' => $origem,
                'dados_extras' => array_filter([
                    'empresa' => $pessoa['razao'] ?? null,
                    'valor_conta' => round($pessoa['perfil']['consumo'] * 0.98, -1),
                    'utm_campaign' => in_array($origem, ['Google Ads', 'Facebook', 'Instagram'], true) ? 'energia-solar-'.Str::slug($pessoa['cidade']) : null,
                    'mensagem' => Sorteio::escolher(['Quero um orçamento.', 'Minha conta passa de R$ 500.', 'Tenho interesse em financiar.', 'Gostaria de uma visita.']),
                ]),
                'status' => 'novo',
            ]);
            $this->contagem['leads']++;
            $aoCriar($lead->id);

            $recente = $conversao === null && $t->greaterThan($this->agenda->agora()->subDays(3));
            if ($recente && Sorteio::chance(0.5)) {
                return; // fila do admin: lead novo ainda sem consultor
            }

            $encaminhado = Agenda::depois($t, 0, 1);
            $this->agenda->em($encaminhado, fn () => $this->admin(), function (CarbonImmutable $t2) use ($lead, $login, $conversao) {
                if ($lead->fresh()->status !== 'novo' || ! isset($this->usuarios[$login])) {
                    return; // já convertido antes do encaminhamento formal (ou consultor ainda não contratado: fica na fila)
                }
                $lead->update(['consultor_id' => $this->usuarios[$login]->id, 'status' => 'encaminhado']);

                $this->agenda->em(Agenda::depois($t2, 0, 2), fn () => $this->usuarios[$login], function (CarbonImmutable $t3) use ($lead, $conversao) {
                    if (Lead::whereKey($lead->id)->value('status') !== 'encaminhado') {
                        return; // convertido antes do primeiro contato registrado
                    }
                    $lead->update(['status' => 'contatado', 'anotacoes' => Sorteio::escolher(['Primeiro contato por WhatsApp.', 'Liguei e pedi foto da conta de luz.', 'Retornou o contato pelo site.'])]);

                    // Lead que não virou cliente: depois de algumas semanas, perdido.
                    if ($conversao === null) {
                        $this->agenda->em(Agenda::depois($t3, 10, 35), fn () => $lead->consultor, function () use ($lead) {
                            $lead->update([
                                'status' => 'perdido',
                                'anotacoes' => Sorteio::escolher(['Só queria saber o preço.', 'Imóvel alugado — proprietário não autorizou.', 'Fora da área de atendimento.', 'Não respondeu mais.', 'Consumo baixo demais para compensar.']),
                            ]);
                        });
                    }
                });
            });
        });
    }

    /** Leads que não viram cliente (curiosos, fora do perfil) — volume proporcional ao de clientes. */
    private function agendarLeadsNaoConvertidos(): void
    {
        $agora = $this->agenda->agora();
        $ultimoMes = (int) round($this->inicio->diffInMonths($agora->startOfMonth()));

        for ($mes = 0; $mes <= $ultimoMes; $mes++) {
            foreach (CatalogoDemo::EQUIPE as $membro) {
                if ($membro['tipo'] !== 'consultor' || $membro['entrada'] > $mes || (isset($membro['saida']) && $mes >= $membro['saida'])) {
                    continue;
                }
                $quantidade = (int) round((Sorteio::entre(1, 3) + intdiv($mes - $membro['entrada'], 4)) * $this->escala);
                for ($i = 0; $i < $quantidade; $i++) {
                    $quando = Agenda::expediente($this->dataDoMes($mes, Sorteio::entre($mes === $membro['entrada'] ? 8 : 1, 28)));
                    if ($quando->greaterThan($agora)) {
                        continue;
                    }
                    $uf = (string) array_key_first($membro['cidades']);
                    $this->agendarLead($this->pessoa($uf, Sorteio::escolher($membro['cidades'][$uf])), $membro['login'], $quando, null, fn () => null);
                }
            }
        }
    }

    // ── Ciclo do orçamento ────────────────────────────────────────────────

    /** @param  array<string, mixed>  $perfil */
    private function novoOrcamento(int $clienteId, array $perfil, CarbonImmutable $quando, ?string $anotacoes = null): void
    {
        $cliente = Cliente::with('cidade')->find($clienteId);
        $consultor = User::find($cliente->consultor_id);
        $consultor = $consultor->status ? $consultor : $this->substitutoDe($consultor);

        $orcamento = $this->fabrica->criar($cliente, $consultor, $this->comTarifas($perfil), $anotacoes);
        $this->contagem['orcamentos']++;
        if ($cliente->status === 'novo') {
            $cliente->update(['status' => 'orcamento_gerado']);
        }

        $this->planejar($orcamento, $quando, $perfil);
    }

    /** Tarifas da concessionária vigentes na data (inclui os reajustes já aplicados). */
    private function comTarifas(array $perfil): array
    {
        $c = Concessionaria::where('nome', $perfil['concessionaria'])->firstOrFail();
        $tributos = 1.27; // ICMS/PIS/COFINS sobre a tarifa homologada

        if ($perfil['demanda'] ?? false) {
            $perfil += [
                'tarifa_ponta' => round((float) $c->tarifa_ponta * $tributos, 5),
                'tarifa_fp' => round((float) $c->tarifa_fora_ponta * $tributos, 5),
                'tarifa_demanda_ponta' => Sorteio::decimal(38, 62),
                'tarifa_demanda_fp' => $perfil['modalidade'] === 'THS_AZUL' ? Sorteio::decimal(16, 26) : null,
            ];
            $perfil['valor_conta'] = round($perfil['consumo_ponta'] * $perfil['tarifa_ponta'] + $perfil['consumo_fora_ponta'] * $perfil['tarifa_fp']
                + $perfil['demanda_ponta'] * $perfil['tarifa_demanda_ponta'], 2);

            return $perfil;
        }

        $perfil['tarifa'] = round((float) $c->tarifa_convencional * $tributos * Sorteio::decimal(0.98, 1.03, 4), 5);
        $perfil['valor_conta'] = round($perfil['consumo'] * $perfil['tarifa'] + Sorteio::decimal(18, 45), 2);

        return $perfil;
    }

    /**
     * Plano do ciclo: por quais etapas passa, onde termina (ganho, perdido ou parado) e o ritmo.
     * Os passos são agendados um a um; o que cai depois de hoje fica pendente.
     *
     * @param  array<string, mixed>  $perfil
     */
    private function planejar(Orcamento $orcamento, CarbonImmutable $inicio, array $perfil, bool $reativado = false): void
    {
        $idade = $inicio->diffInDays($this->agenda->agora());
        $pf = $orcamento->cliente?->tipo_pessoa !== 'pj';

        $etapas = [1, 2];
        if (Sorteio::chance(match ($perfil['grupo']) {
            'B1' => 0.55, 'B2' => 0.7, 'B3' => 0.65, default => 1.0
        })) {
            $etapas[] = 3;
        }
        $etapas[] = 4;
        if (Sorteio::chance($pf ? 0.4 : 0.25)) {
            $etapas[] = 5;
        }
        $etapas[] = 6;

        $destino = (string) Sorteio::ponderado([
            'ganho' => $reativado ? 40 : ($idade < 7 ? 60 : 36),
            'perdido' => $reativado ? 60 : 52,
            'parado' => $reativado ? 0 : ($idade > 90 ? 1 : ($idade > 30 ? 3 : 6)),
        ]);
        // Fechamento relâmpago: cliente decidido assina na primeira visita (contato → proposta → fechamento).
        // Mais comum entre os recentes, para o mês corrente já ter vendas em qualquer dia em que a base for gerada.
        $relampago = $destino === 'ganho' && Sorteio::chance(match (true) {
            $idade < 7 => 1.0, $idade < 45 => 0.15, default => 0.08
        });
        if ($relampago) {
            $etapas = [1, 2, 6];
        }

        // Perde-se mais no começo do funil; para cada etapa, o motivo típico.
        $pesos = array_map(fn ($i) => max(1, 6 - $i), array_keys($etapas));
        $ondeTermina = $destino === 'ganho' ? count($etapas) - 1 : (int) Sorteio::ponderado($pesos);

        $this->planos[$orcamento->id] = [
            'relampago' => $relampago,
            'etapas' => $etapas,
            'destino' => $destino,
            'termina_em' => $ondeTermina,
            // Indicações e clientes decididos fecham em poucos dias; o restante leva de 3 a 6 semanas.
            'ritmo' => match (true) {
                $relampago => 0.05,
                Sorteio::chance($destino === 'ganho' ? 0.35 : 0.1) => Sorteio::decimal(0.2, 0.45),
                default => Sorteio::decimal(0.8, 1.3),
            },
            'perfil' => $perfil,
            'avulso' => Sorteio::chance($perfil['grupo'] === 'B1' ? 0.1 : 0.3),
            'reprovar' => $destino === 'ganho' && Sorteio::chance(0.07),
            'cancelar_contrato' => $destino === 'ganho' && Sorteio::chance(0.02),
        ];

        // Parte dos parados nunca saiu da caixa de entrada (esfria e entra na Recuperação).
        if ($destino === 'parado' && ! $reativado && $idade < 20 && Sorteio::chance(0.4)) {
            return;
        }

        // Iniciar atendimento: quase sempre no mesmo dia ou no seguinte; às vezes esquecido na caixa.
        $atraso = match ((string) Sorteio::ponderado(['rapido' => 75, 'depois' => 20, 'esquecido' => 5])) {
            'rapido' => [0, 1], 'depois' => [2, 4], default => [5, 9],
        };
        $this->agenda->em(Agenda::depois($inicio, $atraso[0], $atraso[1]), fn () => $this->dono($orcamento->id), fn (CarbonImmutable $t) => $this->avancar($orcamento->id, 0, $t));
    }

    /** Leva o orçamento à etapa $i do plano e agenda o próximo passo. */
    private function avancar(int $id, int $i, CarbonImmutable $t): void
    {
        $plano = $this->planos[$id];
        $orcamento = Orcamento::find($id);
        if (! $orcamento || $orcamento->estaPerdido()) {
            return;
        }

        $etapa = $this->etapas[$plano['etapas'][$i] - 1];
        $origem = (string) ($this->funil->etapaDe($orcamento)->id ?? FunilService::CAIXA);
        $ultima = $i === $plano['termina_em'];
        $proximoPasso = $this->proximaData($plano, $i, $t);

        $ok = $this->tentar(function () use ($orcamento, $etapa, $origem, $proximoPasso, $t, $i, $ultima, $plano) {
            if ($i > 0 && Sorteio::chance(0.55)) {
                $this->funil->registrarContato($orcamento, $this->dono($orcamento->id), Sorteio::escolher(CatalogoDemo::NOTAS_CONTATO), $proximoPasso);
            }
            $parado = $ultima && $plano['destino'] === 'parado';
            $this->funil->mover($orcamento, $etapa, $this->dono($orcamento->id), $origem, $parado ? Agenda::depois($t, 2, 7) : $proximoPasso);

            // Às vezes o consultor registra a conversa mas não agenda o retorno: "sem próximo passo" no quadro.
            if (! $parado && $i > 0 && Sorteio::chance(0.08)) {
                $this->funil->registrarContato($orcamento, $this->dono($orcamento->id), Sorteio::escolher(CatalogoDemo::NOTAS_CONTATO), null);
            }
        });
        if (! $ok) {
            return;
        }

        if ($etapa->ordem === 3) {
            $this->agendarVisita($id, $t);
        }
        if ($etapa->ordem === 4 && $plano['avulso']) {
            $this->fabrica->adicionarItemAvulso($orcamento->fresh(['info']));
        }

        if (! $ultima) {
            $this->agenda->em($proximoPasso, fn () => $this->dono($id), fn (CarbonImmutable $t2) => $this->avancar($id, $i + 1, $t2));

            return;
        }

        match ($plano['destino']) {
            'ganho' => $this->agenda->em($proximoPasso, fn () => $this->dono($id), fn (CarbonImmutable $t2) => $this->enviarParaAprovacao($id, $t2)),
            'perdido' => $this->agenda->em($proximoPasso, fn () => $this->dono($id), fn (CarbonImmutable $t2) => $this->perder($id, $etapa->ordem, $t2)),
            default => null, // parado: o próximo contato vai vencer sem ninguém agir
        };
    }

    private function proximaData(array $plano, int $i, CarbonImmutable $t): CarbonImmutable
    {
        $etapaAtual = $plano['etapas'][$i];
        [$min, $max] = match ($etapaAtual) {
            1 => [1, 2], 2 => [2, 6], 3 => [3, 8], 4 => [3, 9], 5 => [6, 15], default => [1, 5],
        };

        return Agenda::depois($t, (int) round($min * $plano['ritmo']), max(1, (int) round($max * $plano['ritmo'])));
    }

    private function perder(int $id, int $ordemEtapa, CarbonImmutable $t): void
    {
        $orcamento = Orcamento::find($id);
        $motivo = (string) Sorteio::ponderado(match ($ordemEtapa) {
            1, 2 => ['Sem retorno do cliente' => 35, 'Adiou a decisão' => 25, 'Preço alto' => 20, 'Desistiu do projeto' => 10, 'Fechou com concorrente' => 10],
            3 => ['Inviabilidade técnica' => 30, 'Preço alto' => 25, 'Adiou a decisão' => 20, 'Fechou com concorrente' => 15, 'Sem retorno do cliente' => 10],
            5 => ['Crédito/financiamento negado' => 60, 'Adiou a decisão' => 20, 'Desistiu do projeto' => 20],
            default => ['Fechou com concorrente' => 35, 'Preço alto' => 35, 'Adiou a decisão' => 20, 'Sem retorno do cliente' => 10],
        });
        $origem = (string) ($this->funil->etapaDe($orcamento)->id ?? FunilService::CAIXA);
        $obs = Sorteio::chance(0.75) ? Sorteio::escolher(CatalogoDemo::OBSERVACOES_PERDA[$motivo]) : null;

        if (! $this->tentar(fn () => $this->funil->perder($orcamento, $this->motivos[$motivo], $obs, $this->dono($id), $origem))) {
            return;
        }
        $this->contagem['perdidos']++;

        // Parte dos perdidos reativáveis volta ao funil depois do prazo do motivo.
        $m = $this->motivos[$motivo];
        if ($m->reativavel && Sorteio::chance(0.14)) {
            $quando = Agenda::depois($t, (int) $m->reativar_apos_dias, (int) $m->reativar_apos_dias + 25);
            $this->agenda->em($quando, fn () => $this->dono($id), function (CarbonImmutable $t2) use ($id) {
                $orcamento = Orcamento::find($id);
                $primeiroContato = Agenda::depois($t2, 1, 4);
                if ($this->tentar(fn () => $this->funil->reativar($orcamento, $this->dono($id), null, $primeiroContato))) {
                    $this->contagem['reativados']++;
                    $this->planejarRetomada($orcamento, $primeiroContato);
                }
            });
        }
    }

    /** Depois de reativado, o orçamento já está na primeira etapa: segue um plano novo a partir dela. */
    private function planejarRetomada(Orcamento $orcamento, CarbonImmutable $quando): void
    {
        $perfil = $this->planos[$orcamento->id]['perfil'];
        $this->planejar($orcamento, $quando, $perfil, true);
        // planejar() agenda o passo 0 (caixa → etapa 1); a retomada já está na etapa 1, então o passo 0 só registra contato.
    }

    // ── Aprovação, contrato e instalação ─────────────────────────────────

    private function enviarParaAprovacao(int $id, CarbonImmutable $t): void
    {
        $orcamento = Orcamento::find($id);
        $origem = (string) $this->funil->etapaDe($orcamento)->id;
        $aprovacao = FunilEtapa::where('tipo', FunilEtapa::APROVACAO)->first();

        $enviado = $this->tentar(function () use ($orcamento, $aprovacao, $origem, $id, $t) {
            $this->funil->mover($orcamento, $aprovacao, $this->dono($id), $origem);
            // O consultor avisa o cliente e marca o retorno para depois da aprovação interna.
            $this->funil->registrarContato($orcamento, $this->dono($id), 'Cliente aceitou a proposta; enviado para aprovação interna.', Agenda::depois($t, 3, 5));
        });
        if (! $enviado) {
            return;
        }

        $espera = $this->planos[$id]['relampago'] ? [0, 1] : [1, 3];
        $this->agenda->em(Agenda::depois($t, ...$espera), fn () => $this->admin(), fn (CarbonImmutable $t2) => $this->decidirAprovacao($id, $t2));
    }

    private function decidirAprovacao(int $id, CarbonImmutable $t): void
    {
        $orcamento = Orcamento::find($id);
        $admin = auth()->user();
        $aprovacao = (string) $this->funil->etapaDe($orcamento)->id;

        if ($this->planos[$id]['reprovar']) {
            $this->planos[$id]['reprovar'] = false;
            $negociacao = $this->etapas->firstWhere('ordem', 4);
            if ($this->tentar(fn () => $this->funil->mover($orcamento, $negociacao, $admin, $aprovacao))) {
                $this->contagem['reprovados']++;
                // Consultor corrige (desconto fora da política, item faltando) e reenvia.
                $this->agenda->em(Agenda::depois($t, 2, 5), fn () => $this->dono($id), fn (CarbonImmutable $t2) => $this->enviarParaAprovacao($id, $t2));
            }

            return;
        }

        $ganho = FunilEtapa::where('tipo', FunilEtapa::GANHO)->first();
        if (! $this->tentar(fn () => $this->funil->mover($orcamento, $ganho, $admin, $aprovacao))) {
            return;
        }
        $this->contagem['ganhos']++;

        $this->agenda->em(Agenda::depois($t, 1, 4), fn () => $this->dono($id), fn (CarbonImmutable $t2) => $this->gerarContrato($id, $t2));
    }

    /** Como o ContratosController::store: valor e dados técnicos vêm do orçamento aprovado. */
    private function gerarContrato(int $id, CarbonImmutable $t): void
    {
        $orcamento = Orcamento::with(['itens.kit.componentes', 'info', 'cliente.cidade'])->find($id);
        if ($orcamento->status !== 'aprovado' || $orcamento->contrato()->exists()) {
            return;
        }
        $cliente = $orcamento->cliente;
        $itemKit = $orcamento->itens->firstWhere('tipo', 'kit');
        $componentes = $itemKit->kit->componentes ?? collect();
        $painel = $componentes->first(fn ($p) => $p->categoria?->slug === 'painel-solar');
        $inversor = $componentes->first(fn ($p) => $p->categoria?->slug === 'inversor-solar');
        $qtdKits = (int) ($itemKit->quantidade ?? 1);
        $financiado = in_array(5, $this->planos[$id]['etapas'], true);

        Contrato::create([
            'orcamento_id' => $orcamento->id,
            'consultor_id' => auth()->id(),
            'nome_cliente' => $cliente->nome_display,
            'documento_cliente' => $cliente->cpf ?? $cliente->cnpj ?? '000.000.000-00',
            'endereco_instalacao' => "{$cliente->rua}, {$cliente->numero} — {$cliente->bairro}, {$cliente->cidade->cidade}/{$cliente->cidade->sigla}",
            'potencia_kwp' => round($orcamento->itens->where('tipo', 'kit')->sum(fn ($i) => (float) ($i->metadados['potencia_kwp'] ?? 0)), 3),
            'qtd_paineis' => (int) (($painel?->pivot->quantidade ?? 10) * $qtdKits),
            'qtd_inversores' => (int) (($inversor?->pivot->quantidade ?? 1) * $qtdKits),
            'modelo_inversor' => $inversor->nome ?? 'Conforme projeto',
            'consumo_mensal' => (int) round((float) $orcamento->info->consumo),
            'geracao_estimada' => $orcamento->geracao_estimada,
            'garantia_paineis' => '12 anos contra defeitos de fabricação e 30 anos de performance linear',
            'garantia_inversores' => str_contains((string) $inversor?->nome, 'Fronius') ? '7 anos (extensível)' : '10 anos',
            'valor_total' => $orcamento->preco_total,
            'formas_pagamento' => $financiado
                ? 'Financiamento '.Sorteio::escolher(['Banco Horizonte — Crédito Solar em 60x', 'Cooperativa Vale Verde em 72x', 'Financeira Sol+ em 48x']).' com carência de 90 dias.'
                : Sorteio::escolher([
                    'Pix à vista com 5% de desconto — chave pagamentos@'.CatalogoDemo::DOMINIO_EQUIPE.', recebedor '.Ficticio::RECEBEDOR.'.',
                    '30% de entrada via Pix ('.Ficticio::RECEBEDOR.') e saldo na conclusão da instalação.',
                    'Cartão de crédito em até 12x (link de pagamento '.Ficticio::RECEBEDOR.').',
                ]),
            'clausulas_adicionais' => Sorteio::chance(0.3) ? 'Instalação condicionada à aprovação do projeto pela concessionária.' : null,
            'produtos_snapshot' => $orcamento->itens->map(fn ($item) => [
                'descricao' => $item->descricao, 'quantidade' => $item->quantidade,
                'preco_venda_unitario' => (float) $item->preco_venda_unitario, 'preco_venda_total' => (float) $item->preco_venda_total,
            ])->all(),
            'status' => 'gerado',
        ]);
        $this->contagem['contratos']++;

        $this->agenda->em(Agenda::depois($t, 2, 8), fn () => $this->dono($id), fn (CarbonImmutable $t2) => $this->assinarContrato($id, $t2));
    }

    private function assinarContrato(int $id, CarbonImmutable $t): void
    {
        $orcamento = Orcamento::with(['contrato', 'info', 'cliente'])->find($id);
        $orcamento->contrato->update(['status' => 'assinado']);
        $orcamento->info?->update(['bloquear_edicao' => true]);
        OrcamentoAprovacao::create([
            'orcamento_id' => $id,
            'nome_assinante' => $orcamento->contrato->nome_cliente,
            'documento_assinante' => $orcamento->contrato->documento_cliente,
            'ip_assinatura' => '192.0.2.'.Sorteio::entre(1, 254), // faixa TEST-NET (RFC 5737)
            'forma_pagamento' => Str::limit($orcamento->contrato->formas_pagamento, 120),
            'observacoes' => 'Assinatura eletrônica (demonstração).',
            'assinado_em' => $t,
        ]);

        if ($this->planos[$id]['cancelar_contrato']) {
            // Exceção de propósito: cliente desistiu depois de assinar.
            $this->agenda->em(Agenda::depois($t, 5, 18), fn () => $this->admin(), function () use ($orcamento) {
                $orcamento->contrato->update(['status' => 'cancelado', 'clausulas_adicionais' => 'Distrato assinado: cliente desistiu antes da instalação; sinal devolvido.']);
                $orcamento->update(['anotacoes' => trim(($orcamento->anotacoes ?? '').' Contrato cancelado a pedido do cliente — aguardando decisão sobre o orçamento.')]);
            });

            return;
        }

        $this->agenda->em(Agenda::depois($t, 6, 18), fn () => $this->admin(), fn (CarbonImmutable $t2) => $this->statusPeloAdmin($id, 'instalando', $t2));
    }

    /** Mudança de status pela tela do orçamento do Admin (OrcamentosController::update). */
    private function statusPeloAdmin(int $id, string $status, CarbonImmutable $t): void
    {
        $orcamento = Orcamento::find($id);
        $anterior = $orcamento->status;
        if ($orcamento->estaPerdido() || ! $orcamento->podeIrPara($status)) {
            return;
        }

        $orcamento->update(['status' => $status]);
        OrcamentoHistorico::create([
            'orcamento_id' => $id, 'usuario_id' => auth()->id(), 'status' => $status,
            'mensagem' => "Status alterado pelo administrador: {$anterior} → {$status}.",
        ]);

        if ($status === 'instalando') {
            $this->agenda->em(Agenda::depois($t, 15, 40), fn () => $this->admin(), fn (CarbonImmutable $t2) => $this->statusPeloAdmin($id, 'finalizado', $t2));

            return;
        }

        $orcamento->cliente?->update(['status' => 'finalizado']);
        $this->agendarPosVenda($orcamento, $t);
    }

    // ── Visitas técnicas ──────────────────────────────────────────────────

    private function agendarVisita(int $id, CarbonImmutable $t, bool $reagendada = false): void
    {
        $orcamento = Orcamento::with('cliente')->find($id);
        $data = Agenda::expediente($t->addDays(Sorteio::entre(1, 4)))->setTime(Sorteio::chance(0.5) ? 12 : 17, 0); // 9h ou 14h em Brasília
        $visita = VisitaTecnica::create([
            'consultor_id' => auth()->id(),
            'cliente_id' => $orcamento->cliente_id,
            'orcamento_id' => $id,
            'data_agendada' => $data,
            'status' => 'agendada',
            'anotacoes' => $reagendada ? 'Reagendada a pedido do cliente.' : Sorteio::escolher(['Levar trena e drone.', 'Verificar padrão de entrada.', 'Cliente pediu visita no fim da tarde.', null]),
        ]);
        $this->contagem['visitas']++;
        if ($orcamento->cliente && in_array($orcamento->cliente->status, ['novo', 'orcamento_gerado'], true)) {
            $orcamento->cliente->update(['status' => 'visita_agendada']);
        }

        $this->agenda->em($data->addMinutes(Sorteio::entre(30, 120)), fn () => $this->dono($id), function (CarbonImmutable $t2) use ($visita, $id, $reagendada) {
            if (! $reagendada && Sorteio::chance(0.08)) {
                $visita->update(['status' => 'cancelada', 'anotacoes' => 'Cliente não estava no local.']);
                $this->agendarVisita($id, $t2->addDays(2), true);

                return;
            }
            $visita->update(['status' => 'realizada', 'anotacoes' => trim(($visita->anotacoes ?? '').' Vistoria concluída; fotos anexadas.')]);
            OrcamentoVistoria::updateOrCreate(['orcamento_id' => $id], [
                'largura_telhado' => Sorteio::decimal(6, 30), 'altura_telhado' => Sorteio::decimal(4, 18),
                'tipo_disjuntor' => Sorteio::escolher(['Monopolar 40 A', 'Bipolar 50 A', 'Tripolar 63 A', 'Tripolar 100 A']),
                'padrao_energia' => Sorteio::escolher(['Monofásico', 'Bifásico', 'Trifásico']),
                'tipo_telhado' => Sorteio::escolher(['Cerâmico', 'Fibrocimento', 'Metálico', 'Laje']),
                'tipo_fiacao' => Sorteio::escolher(['Embutida', 'Aparente em eletroduto']),
                'tipo_medidor' => Sorteio::escolher(['Eletrônico', 'Eletromecânico']),
                'observacoes' => Sorteio::escolher(['Telhado em bom estado.', 'Necessária troca de 6 telhas quebradas.', 'Sombreamento parcial da caixa d’água no fim da tarde.', 'Quadro precisa de barramento novo.']),
                'imagens' => [],
            ]);
        });
    }

    // ── Pós-venda ─────────────────────────────────────────────────────────

    private function agendarPosVenda(Orcamento $orcamento, CarbonImmutable $t): void
    {
        $clienteId = $orcamento->cliente_id;

        foreach ([[0.45, 30, 200], [0.15, 210, 380]] as [$chance, $min, $max]) {
            if (Sorteio::chance($chance)) {
                $this->agenda->em(Agenda::depois($t, $min, $max), fn () => $this->dono($orcamento->id), function (CarbonImmutable $t2) use ($clienteId) {
                    $this->novaPropostaServico($clienteId, $t2);
                });
            }
        }

        // Ampliação: cliente satisfeito aumenta o consumo (carro elétrico, ar-condicionado, expansão).
        if (Sorteio::chance(0.07)) {
            $perfil = $this->planos[$orcamento->id]['perfil'] ?? null;
            if ($perfil && ! ($perfil['demanda'] ?? false)) {
                $perfil['consumo'] = round($perfil['consumo'] * Sorteio::decimal(0.35, 0.7) / 10) * 10;
                $this->agenda->em(Agenda::depois($t, 90, 260), fn () => $this->dono($orcamento->id), function (CarbonImmutable $t2) use ($clienteId, $perfil) {
                    $this->contagem['ampliacoes']++;
                    $this->novoOrcamento($clienteId, $perfil, $t2, 'Ampliação do sistema instalado: '.Sorteio::escolher(['cliente comprou carro elétrico.', 'instalou ar-condicionado em todos os quartos.', 'expansão da área produtiva.']));
                });
            }
        }
    }

    private function novaPropostaServico(int $clienteId, CarbonImmutable $t): void
    {
        [$titulo, $min, $max] = Sorteio::escolher(CatalogoDemo::SERVICOS);
        $valor = round(Sorteio::decimal($min, $max) / 10) * 10;
        $conteudo = "<p>{$titulo} para a usina instalada.</p><ul><li>Execução por equipe própria</li><li>Relatório com fotos e medições</li><li>Garantia de 90 dias sobre o serviço</li></ul>";

        $proposta = PropostaServico::create([
            'consultor_id' => auth()->id(),
            'cliente_id' => $clienteId,
            'titulo' => $titulo,
            'descricao' => Str::limit(strip_tags($conteudo), 250),
            'conteudo' => $conteudo,
            'valor' => $valor,
            'validade' => $t->addDays(15)->toDateString(),
            'prazo_final' => $t->addDays(Sorteio::entre(20, 45))->toDateString(),
            'status' => Sorteio::chance(0.25) ? 'rascunho' : 'enviada',
            'observacoes' => Sorteio::chance(0.3) ? 'Condição especial para clientes com usina instalada pela empresa.' : null,
        ]);
        $this->contagem['propostas']++;

        if ($proposta->status === 'rascunho') {
            $this->agenda->em(Agenda::depois($t, 1, 5), fn () => User::find($proposta->consultor_id), function () use ($proposta) {
                $proposta->update(['status' => 'enviada']);
            });
        }
        $desfecho = (string) Sorteio::ponderado(['aceita' => 50, 'recusada' => 25, 'expirada' => 25]);
        $quando = $desfecho === 'expirada' ? $t->addDays(16) : Agenda::depois($t, 3, 14);
        $this->agenda->em($quando, fn () => User::find($proposta->consultor_id), function () use ($proposta, $desfecho) {
            $proposta->update(['status' => $desfecho]);
        });
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    /** Executa uma movimentação; recusa do funil encerra aquele caminho (e é contada no resumo). */
    private function tentar(callable $acao): bool
    {
        try {
            $acao();

            return true;
        } catch (MovimentoInvalido) {
            $this->contagem['recusas_do_funil']++;

            return false;
        }
    }

    private function dataDoMes(int $mes, int $dia): CarbonImmutable
    {
        $inicioMes = $this->inicio->addMonths($mes);

        return $inicioMes->setDay(min($dia, $inicioMes->daysInMonth));
    }

    private function nomeUnico(bool $feminino): string
    {
        do {
            $nome = Sorteio::escolher($feminino ? CatalogoDemo::NOMES_F : CatalogoDemo::NOMES_M).' '
                .Sorteio::escolher(CatalogoDemo::SOBRENOMES).' '.Sorteio::escolher(CatalogoDemo::SOBRENOMES);
        } while (isset($this->nomesUsados[$nome]));

        $this->nomesUsados[$nome] = true;

        return $nome;
    }

    private function razaoUnica(string $segmento): string
    {
        do {
            $razao = "{$segmento} ".Sorteio::escolher(CatalogoDemo::FANTASIAS).' '.Sorteio::escolher(CatalogoDemo::SUFIXOS_PJ);
        } while (isset($this->nomesUsados[$razao]));

        $this->nomesUsados[$razao] = true;

        return $razao;
    }
}
