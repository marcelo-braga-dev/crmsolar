<?php

namespace Database\Seeders\Support;

use App\Models\Cliente;
use App\Models\Concessionaria;
use App\Models\Estrutura;
use App\Models\Kit;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Models\OrcamentoInfo;
use App\Models\OrcamentoItem;
use App\Models\Produto;
use App\Models\User;
use App\Services\DimensionamentoService;
use App\Services\GrupoTarifarioService;
use App\Services\PrecificacaoService;
use Illuminate\Support\Collection;

/**
 * Cria orçamentos como os fluxos B1/B2/B3/Grupo A do consultor (BaseGrupoController::salvarOrcamento):
 * mesmos serviços de dimensionamento, precificação (3 camadas de margem) e análise econômica,
 * então preço, geração e payback seguem exatamente a fórmula do sistema.
 */
final class FabricaOrcamento
{
    /** @var Collection<string, int> */
    private Collection $estruturas;

    /** @var Collection<string, Concessionaria> */
    private Collection $concessionarias;

    public function __construct(
        private readonly DimensionamentoService $dimensionamento,
        private readonly GrupoTarifarioService $grupos,
    ) {
        $this->estruturas = Estrutura::pluck('id', 'nome');
        $this->concessionarias = Concessionaria::all()->keyBy('nome');
    }

    /**
     * @param  array<string, mixed>  $perfil  gerado por SimuladorComercial::perfilDeConsumo()
     */
    public function criar(Cliente $cliente, User $consultor, array $perfil, ?string $anotacoes = null): Orcamento
    {
        $grupo = $perfil['grupo'];
        $params = $this->dimensionamento->getParams();
        $hsp = $this->dimensionamento->getIrradiacao($cliente->cidade_id);
        $orientacao = (string) Sorteio::ponderado(['norte' => 45, 'nordeste_noroeste' => 30, 'leste_oeste' => 18, 'sudeste_sudoeste' => 5, 'sul' => 2]);
        $concessionaria = $this->concessionarias[$perfil['concessionaria']];
        $uf = $cliente->cidade->sigla;

        $estrutura = (string) Sorteio::ponderado(match (true) {
            $grupo === 'B1' => ['Telha Colonial (Cerâmico)' => 55, 'Laje' => 20, 'Telha Metálica Perfil 55cm' => 15, 'Telha Ondulada' => 10],
            $grupo === 'B3' => ['Telha Metálica Perfil 55cm' => 60, 'Telha Ondulada' => 20, 'Laje' => 20],
            $grupo === 'B2' => ['Solo' => 50, 'Telha Metálica Perfil 55cm' => 30, 'Telha Ondulada' => 20],
            default => ['Solo' => 45, 'Telha Metálica Perfil 55cm' => 55],
        });
        $tensao = match (true) {
            $grupo === 'B1' => 220,
            $grupo === 'B3', $grupo === 'B2' => $perfil['consumo'] > 2500 ? 380 : 220,
            default => 380,
        };

        // Potência como no "Calcular" do formulário.
        if ($perfil['demanda'] ?? false) {
            $potencia = $this->dimensionamento->calcularPotenciaDemanda(
                $perfil['consumo_ponta'], $perfil['consumo_fora_ponta'], $perfil['tarifa_ponta'], $perfil['tarifa_fp'], $hsp, $params, $orientacao,
            );
        } else {
            $potencia = $this->dimensionamento->calcularPotencia($perfil['consumo'] * $perfil['objetivo'] / 100, $hsp, $params, $orientacao);
        }

        [$kit, $qtd] = $this->escolherKit($potencia, $estrutura, $tensao, $perfil['categorias'] ?? [], $grupo);

        $preco = (new PrecificacaoService)->calcular($kit, $qtd, $uf);
        $geracao = $this->dimensionamento->calcularGeracao($hsp, (float) $kit->potencia_kwp * $qtd, $params, $orientacao);

        if ($perfil['demanda'] ?? false) {
            $economia = $this->grupos->economiaGrupoA(
                $geracao, $perfil['consumo_ponta'], $perfil['consumo_fora_ponta'], $perfil['tarifa_ponta'], $perfil['tarifa_fp'],
                $perfil['autoconsumo'], $perfil['modalidade'],
            );
        } else {
            $economia = $this->grupos->economiaGrupoB($geracao, $perfil['consumo'], $perfil['tarifa'], $perfil['fases'], $perfil['objetivo']);
        }
        $analise = $this->grupos->analiseCompleta($preco['preco_venda'], $economia['economia_mensal']);

        $orcamento = Orcamento::create([
            'consultor_id' => $consultor->id,
            'cliente_id' => $cliente->id,
            'cidade_id' => $cliente->cidade_id,
            'status' => 'novo',
            'grupo_tarifario' => $grupo,
            'modalidade_tarifaria' => $perfil['modalidade'] ?? 'convencional',
            'preco_total' => $preco['preco_venda'],
            'geracao_estimada' => $geracao,
            'anotacoes' => $anotacoes,
        ]);

        OrcamentoInfo::create(array_merge([
            'orcamento_id' => $orcamento->id,
            'estrutura_id' => $this->estruturas[$estrutura],
            'concessionaria_id' => $concessionaria->id,
            'tipo_dimensionamento' => ($perfil['demanda'] ?? false) ? 'demanda' : 'convencional',
            'consumo' => $perfil['consumo'],
            'tensao' => $tensao,
            'orientacao' => $orientacao,
            'anotacoes_tecnicas' => $this->anotacaoTecnica($estrutura, $orientacao),
            'analise_economica' => $analise,
            'metadados' => ['pr' => $this->dimensionamento->detalhamentoPR($params, $orientacao), 'hsp' => $hsp],
        ], $this->camposDoGrupo($perfil)));

        OrcamentoItem::create([
            'orcamento_id' => $orcamento->id,
            'tipo' => 'kit',
            'kit_id' => $kit->id,
            'descricao' => $kit->nome,
            'quantidade' => $qtd,
            'preco_custo_unitario' => $kit->preco_custo,
            'preco_venda_unitario' => round($preco['preco_venda'] / $qtd, 2),
            'preco_venda_total' => $preco['preco_venda'],
            'margem_percentual' => $preco['margem_total'],
            'comissao_percentual' => $consultor->comissao_percentual,
            'geracao_estimada' => $geracao,
            'metadados' => ['potencia_kwp' => round((float) $kit->potencia_kwp * $qtd, 3), 'hsp' => $hsp, 'orientacao' => $orientacao],
            'ordem' => 1,
        ]);

        OrcamentoHistorico::create([
            'orcamento_id' => $orcamento->id,
            'usuario_id' => $consultor->id,
            'status' => 'novo',
            'mensagem' => "Orçamento {$grupo} criado.",
        ]);

        return $orcamento;
    }

    /**
     * Item avulso como o OrcamentoItensController (só em orçamento novo): produto do catálogo
     * vendido acima do custo, ou serviço; o total do orçamento é recalculado.
     */
    public function adicionarItemAvulso(Orcamento $orcamento): void
    {
        if ($orcamento->status !== 'novo' || $orcamento->info?->bloquear_edicao) {
            return;
        }

        if (Sorteio::chance(0.55)) {
            [$descricao, $valor] = Sorteio::escolher([['Projeto elétrico, ART e homologação', 1800], ['Adequação do padrão de entrada', 2400], ['Seguro da usina — 1º ano', 950]]);
            $dados = ['tipo' => 'servico', 'produto_id' => null, 'descricao' => $descricao, 'quantidade' => 1,
                'preco_custo_unitario' => 0, 'preco_venda_unitario' => round(Sorteio::ruido($valor, 0.25), 2)];
        } else {
            // Sorteio em PHP: inRandomOrder() ignora a semente no SQLite e quebraria o determinismo.
            $opcoes = Produto::whereIn('sku', ['PRT-SBX-22', 'PRT-DPS-1000', 'EST-ROM-CER4', 'CAB-6MM-PT'])->where('ativo', true)->orderBy('id')->get();
            if ($opcoes->isEmpty()) {
                return;
            }
            $produto = Sorteio::escolher($opcoes->all());
            $quantidade = $produto->unidade === 'm' ? Sorteio::entre(20, 80) : Sorteio::entre(1, 3);
            $dados = ['tipo' => 'produto', 'produto_id' => $produto->id, 'descricao' => $produto->nome, 'quantidade' => $quantidade,
                'preco_custo_unitario' => (float) $produto->preco_custo, 'preco_venda_unitario' => round((float) $produto->preco_custo * Sorteio::decimal(1.35, 1.7), 2)];
        }

        $total = $dados['preco_venda_unitario'] * $dados['quantidade'];
        $custo = $dados['preco_custo_unitario'] * $dados['quantidade'];

        OrcamentoItem::create($dados + [
            'orcamento_id' => $orcamento->id,
            'preco_venda_total' => $total,
            'margem_percentual' => $custo > 0 ? round(($total - $custo) / $custo * 100, 3) : 0,
            'comissao_percentual' => 0,
            'ordem' => $orcamento->itens()->max('ordem') + 1,
        ]);

        $orcamento->preco_total = $orcamento->itens()->sum('preco_venda_total');
        $orcamento->save();
    }

    /**
     * Kit escolhido como o consultor faria: a busca do sistema (mesma tolerância de potência)
     * e, se ela vier vazia, o kit de potência mais próxima na mesma estrutura e tensão.
     *
     * @param  array<int, string>  $categorias
     * @return array{Kit, int}
     */
    private function escolherKit(float $potencia, string $estrutura, int $tensao, array $categorias, string $grupo): array
    {
        $maxPorKit = $grupo === 'B1' ? 20 : 110;
        $qtd = max(1, (int) ceil($potencia / $maxPorKit));
        $qtd = min($qtd, match ($grupo) {
            'B1' => 10, 'B2', 'B3' => 20, default => 30
        });
        $estruturaId = (int) $this->estruturas[$estrutura];

        $kits = $this->dimensionamento->buscarKits($potencia, $estruturaId, $tensao, $qtd, $categorias);
        if ($kits->isEmpty() && $categorias) {
            $kits = $this->dimensionamento->buscarKits($potencia, $estruturaId, $tensao, $qtd);
        }

        if ($kits->isNotEmpty()) {
            // Na maioria das vezes o mais barato da lista; às vezes uma marca preferida.
            $kit = Sorteio::chance(0.6) ? $kits->first() : Sorteio::escolher($kits->take(3)->all());

            return [$kit, $qtd];
        }

        $porKit = $potencia / $qtd;
        $kit = Kit::query()->where('ativo', true)->where('ativo_fornecedor', true)->where('categoria', 'ongrid')
            ->where('estrutura_id', $estruturaId)->where('tensao', $tensao)
            ->get()->sortBy(fn (Kit $k) => abs((float) $k->potencia_kwp - $porKit))->first()
            ?? Kit::where('ativo', true)->where('ativo_fornecedor', true)->where('categoria', 'ongrid')
                ->get()->sortBy(fn (Kit $k) => abs((float) $k->potencia_kwp - $porKit))->firstOrFail();

        return [$kit, $qtd];
    }

    /** Campos de OrcamentoInfo que cada fluxo grava (os mesmos $infoExtra dos controllers). */
    private function camposDoGrupo(array $p): array
    {
        if ($p['demanda'] ?? false) {
            return [
                'consumo_ponta' => $p['consumo_ponta'], 'consumo_fora_ponta' => $p['consumo_fora_ponta'],
                'demanda_contratada' => $p['demanda_ponta'], 'demanda_ponta_kw' => $p['demanda_ponta'],
                'demanda_fora_ponta_kw' => $p['demanda_fp'] ?? null, 'tarifa_kwh_ponta' => $p['tarifa_ponta'], 'tarifa_kwh_fp' => $p['tarifa_fp'],
                'tarifa_demanda_ponta' => $p['tarifa_demanda_ponta'], 'tarifa_demanda_fp' => $p['tarifa_demanda_fp'] ?? null,
                'valor_conta_mensal' => $p['valor_conta'], 'percentual_autoconsumo' => $p['autoconsumo'], 'subgrupo_tensao' => $p['grupo'],
            ];
        }

        return array_filter([
            'tarifa_kwh' => $p['tarifa'],
            'valor_conta_mensal' => $p['valor_conta'],
            'fases' => $p['fases'],
            'disponibilidade_kwh' => GrupoTarifarioService::disponibilidadePorFase($p['fases']),
            'objetivo_percentual' => $p['objetivo'],
            'horario_funcionamento' => $p['horario'] ?? null,
            'percentual_autoconsumo' => $p['autoconsumo'] ?? null,
            'tipo_instalacao' => $p['tipo_instalacao'] ?? null,
            'possui_bombeamento' => $p['bombeamento'] ?? null,
            'consumo_bombeamento' => $p['consumo_bombeamento'] ?? null,
        ], fn ($v) => $v !== null);
    }

    private function anotacaoTecnica(string $estrutura, string $orientacao): ?string
    {
        if (! Sorteio::chance(0.6)) {
            return null;
        }

        return Sorteio::escolher([
            "Estrutura {$estrutura}; orientação {$orientacao}. Sem sombreamento relevante.",
            'Quadro geral com espaço para o disjuntor do inversor.',
            'Padrão de entrada precisa de adequação (disjuntor 63 A).',
            'Telhado com inclinação de 15°; acesso por escada externa.',
            'Ponto de internet próximo ao inversor para o monitoramento.',
        ]);
    }
}
