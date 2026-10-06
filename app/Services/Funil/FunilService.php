<?php

namespace App\Services\Funil;

use App\Models\Cliente;
use App\Models\Config;
use App\Models\FunilEtapa;
use App\Models\MotivoPerda;
use App\Models\Orcamento;
use App\Models\OrcamentoHistorico;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regras do funil de vendas (docs/funil-de-vendas.md): onde cada orçamento aparece no quadro
 * e quais movimentações são permitidas. Toda mudança passa por aqui.
 */
class FunilService
{
    /** Colunas Ganho e Perdido mostram só os fechamentos recentes, para o quadro continuar leve. */
    public const DIAS_FECHADOS_NO_QUADRO = 30;

    /** Cards exibidos por coluna; o total da coluna considera todos. */
    public const LIMITE_POR_COLUNA = 100;

    /** Identificador da caixa de entrada nas requisições (não é uma etapa). */
    public const CAIXA = 'caixa';

    /** Saúde do card (seção 22.3). */
    public const SAUDE_ATRASADO = 'atrasado';

    public const SAUDE_ATENCAO = 'atencao';

    public const SAUDE_SEM_PASSO = 'sem_passo';

    public const SAUDE_EM_DIA = 'em_dia';

    /** @var EloquentCollection<int, FunilEtapa>|null */
    private ?EloquentCollection $etapas = null;

    /** @return EloquentCollection<int, FunilEtapa> */
    public function etapas(): EloquentCollection
    {
        return $this->etapas ??= FunilEtapa::orderBy('ordem')->orderBy('id')->get();
    }

    /** @return Collection<int, FunilEtapa> */
    public function etapasAbertas(): Collection
    {
        return $this->etapas()->where('tipo', FunilEtapa::ABERTA)->where('ativa', true)->values();
    }

    public function etapaSistema(string $tipo): FunilEtapa
    {
        return $this->etapas()->firstWhere('tipo', $tipo)
            ?? throw new MovimentoInvalido("Etapa de sistema \"{$tipo}\" não encontrada.");
    }

    public function primeiraEtapaAberta(): FunilEtapa
    {
        return $this->etapasAbertas()->first()
            ?? throw new MovimentoInvalido('Nenhuma etapa aberta ativa no funil. Configure em Configurações → Funil de vendas.');
    }

    /**
     * Coluna em que o orçamento aparece (null = caixa de entrada). Regra única, seção 6 da documentação:
     * as colunas de sistema são derivadas do status, nunca gravadas em paralelo.
     */
    public function etapaDe(Orcamento $orcamento): ?FunilEtapa
    {
        if ($orcamento->estaPerdido()) {
            return $this->etapaSistema(FunilEtapa::PERDIDO);
        }

        return match (Orcamento::grupoDoStatus($orcamento->status)) {
            FunilEtapa::GANHO => $this->etapaSistema(FunilEtapa::GANHO),
            FunilEtapa::APROVACAO => $this->etapaSistema(FunilEtapa::APROVACAO),
            default => $this->etapaAbertaDe($orcamento),
        };
    }

    private function etapaAbertaDe(Orcamento $orcamento): ?FunilEtapa
    {
        if ($orcamento->funil_etapa_id) {
            // Etapa desativada/removida nunca deixa o card órfão: cai na primeira aberta.
            return $this->etapasAbertas()->firstWhere('id', $orcamento->funil_etapa_id) ?? $this->primeiraEtapaAberta();
        }

        return $orcamento->status === 'novo' ? null : $this->primeiraEtapaAberta();
    }

    // ── Movimentações ─────────────────────────────────────────────────────

    /**
     * Move o card para outra coluna. $origem é a coluna que o usuário viu (id da etapa ou "caixa"):
     * se o card já foi movido por outra pessoa, a operação é recusada.
     */
    public function mover(Orcamento $orcamento, FunilEtapa $destino, User $ator, string $origem, ?CarbonInterface $proximoContato = null): void
    {
        $this->transacao($orcamento, $origem, function (Orcamento $orc, ?FunilEtapa $atual) use ($destino, $ator, $proximoContato) {
            if ($atual?->id === $destino->id) {
                return;
            }

            match ($destino->tipo) {
                FunilEtapa::PERDIDO => throw new MovimentoInvalido('Para marcar como perdido, informe o motivo da perda.'),
                FunilEtapa::GANHO => $this->aprovar($orc, $atual, $ator),
                FunilEtapa::APROVACAO => $this->enviarParaAprovacao($orc, $ator),
                default => $this->moverParaAberta($orc, $atual, $destino, $ator),
            };

            if ($proximoContato && $destino->tipo === FunilEtapa::ABERTA) {
                $orc->update(['proximo_contato_em' => $proximoContato]);
            }
        });
    }

    public function perder(Orcamento $orcamento, MotivoPerda $motivo, ?string $observacao, User $ator, string $origem): void
    {
        $this->transacao($orcamento, $origem, function (Orcamento $orc) use ($motivo, $observacao, $ator) {
            if (! $orc->emNegociacao()) {
                throw new MovimentoInvalido('Só é possível marcar como perdido um orçamento em negociação.');
            }

            $orc->update([
                'perdido_em' => now(),
                'motivo_perda_id' => $motivo->id,
                'perda_observacao' => $observacao,
                'proximo_contato_em' => null,
            ]);

            $this->historico($orc, $ator, 'perda', "Venda perdida: {$motivo->nome}.".($observacao ? " {$observacao}" : ''));
        });
    }

    public function reativar(Orcamento $orcamento, User $ator, ?FunilEtapa $destino = null, ?CarbonInterface $proximoContato = null): void
    {
        $destino ??= $this->primeiraEtapaAberta();
        $this->garantirAbertaAtiva($destino);

        $this->transacao($orcamento, null, function (Orcamento $orc) use ($destino, $ator, $proximoContato) {
            if (! $orc->estaPerdido()) {
                throw new MovimentoInvalido('Este orçamento não está perdido.');
            }

            $this->reativarDentroDaTransacao($orc, $destino, $ator, $proximoContato);
        });
    }

    public function registrarContato(Orcamento $orcamento, User $ator, ?string $nota, ?CarbonInterface $proximoContato): void
    {
        $this->transacao($orcamento, null, function (Orcamento $orc) use ($ator, $nota, $proximoContato) {
            if (in_array($orc->status, Orcamento::STATUS_GANHO, true)) {
                throw new MovimentoInvalido('Venda já fechada: o acompanhamento segue pelo pós-venda.');
            }

            $orc->update(['proximo_contato_em' => $proximoContato]);

            $mensagem = trim(($nota ? "{$nota} " : '').($proximoContato
                ? 'Próximo contato: '.$proximoContato->copy()->setTimezone(config('app.timezone_exibicao'))->format('d/m/Y H:i').'.'
                : 'Sem próximo contato agendado.'));
            $this->historico($orc, $ator, 'contato', $mensagem);
        });
    }

    /**
     * Troca o consultor responsável (só admin, só negociação em andamento). O percentual de comissão
     * gravado nos itens passa a ser o do novo consultor: a comissão é de quem fecha a venda.
     *
     * @return bool false quando o consultor já era o responsável (nada muda)
     */
    public function reatribuir(Orcamento $orcamento, User $novo, User $ator): bool
    {
        $this->exigirAdmin($ator, 'Só o administrador reatribui negociações.');

        if (! $novo->isConsultor() || ! $novo->status) {
            throw new MovimentoInvalido('Escolha um consultor ativo.');
        }

        $mudou = false;
        $this->transacao($orcamento, null, function (Orcamento $orc) use ($novo, $ator, &$mudou) {
            if (! $orc->emNegociacao()) {
                throw new MovimentoInvalido('Só negociações em andamento podem ser reatribuídas.');
            }
            if ($orc->consultor_id === $novo->id) {
                return;
            }
            $mudou = true;

            $anterior = $orc->consultor->name ?? 'sem consultor';
            $orc->update(['consultor_id' => $novo->id]);
            // Pelo model (não query builder) para a alteração de comissão ficar na Auditoria.
            $orc->itens()->get()->each->update(['comissao_percentual' => (float) ($novo->comissao_percentual ?? 0)]);

            $this->historico($orc, $ator, 'responsavel', "Responsável: {$anterior} → {$novo->name}.");
        });

        return $mudou;
    }

    /**
     * Aplica a mesma ação a vários orçamentos (só admin). Cada um é validado como se fosse movido
     * sozinho; as recusas não impedem os demais.
     *
     * @param  array<int, int>  $ids
     * @param  array{etapa_id?: int, consultor_id?: int, motivo_perda_id?: int, observacao?: string|null}  $dados
     * @return array{feitos: int, recusados: array<int, string>}
     */
    public function lote(array $ids, string $acao, array $dados, User $ator): array
    {
        $this->exigirAdmin($ator, 'Só o administrador executa ações em lote.');

        $resultado = ['feitos' => 0, 'recusados' => []];

        foreach (Orcamento::whereIn('id', $ids)->orderBy('id')->get() as $orcamento) {
            try {
                if ($acao === 'mover' && ! $orcamento->emNegociacao()) {
                    // Em lote não se aprova, reprova nem reativa: isso é decisão caso a caso.
                    throw new MovimentoInvalido('Só negociações em andamento podem ser movidas em lote.');
                }
                $origem = (string) ($this->etapaDe($orcamento)->id ?? self::CAIXA);
                match ($acao) {
                    'mover' => $this->mover($orcamento, FunilEtapa::findOrFail($dados['etapa_id']), $ator, $origem),
                    'reatribuir' => $this->reatribuir($orcamento, User::findOrFail($dados['consultor_id']), $ator),
                    'perder' => $this->perder($orcamento, MotivoPerda::findOrFail($dados['motivo_perda_id']), $dados['observacao'] ?? null, $ator, $origem),
                    default => throw new MovimentoInvalido("Ação em lote desconhecida: {$acao}."),
                };
                $resultado['feitos']++;
            } catch (MovimentoInvalido $e) {
                $resultado['recusados'][$orcamento->id] = $e->getMessage();
            }
        }

        return $resultado;
    }

    /** Dados do painel lateral do card: cliente, contatos, itens e linha do tempo. */
    public function detalhe(Orcamento $orcamento): array
    {
        $orcamento->load([
            'cliente', 'cidade:id,cidade,sigla', 'consultor:id,name', 'motivoPerda:id,nome', 'contrato:id,orcamento_id,status',
            'itens' => fn ($q) => $q->orderBy('ordem'),
            'historicos' => fn ($q) => $q->with('usuario:id,name')->latest('id')->limit(30),
        ]);
        $etapa = $this->etapaDe($orcamento);

        return [
            ...$this->card($orcamento, $etapa),
            'etapa' => $etapa ? ['id' => $etapa->id, 'nome' => $etapa->nome, 'cor' => $etapa->cor, 'tipo' => $etapa->tipo] : null,
            'email' => $orcamento->cliente?->email,
            'geracao_estimada' => (int) $orcamento->geracao_estimada,
            'criado_em' => $orcamento->created_at->toIso8601String(),
            'perda_observacao' => $orcamento->perda_observacao,
            'itens' => $orcamento->itens->map(fn ($i) => [
                'descricao' => $i->descricao,
                'tipo' => $i->tipo,
                'quantidade' => (float) $i->quantidade,
                'total' => (float) $i->preco_venda_total,
            ])->values()->all(),
            'historico' => $orcamento->historicos->map(fn (OrcamentoHistorico $h) => [
                'id' => $h->id,
                'tipo' => $h->tipo,
                'mensagem' => $h->mensagem,
                'usuario' => $h->usuario?->name,
                'em' => $h->created_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function moverParaAberta(Orcamento $orc, ?FunilEtapa $atual, FunilEtapa $destino, User $ator): void
    {
        $this->garantirAbertaAtiva($destino);

        if ($orc->estaPerdido()) {
            $this->reativarDentroDaTransacao($orc, $destino, $ator);

            return;
        }

        if ($atual?->tipo === FunilEtapa::GANHO) {
            throw new MovimentoInvalido('Venda ganha não volta pelo quadro. Use a tela do orçamento.');
        }

        if ($atual?->tipo === FunilEtapa::APROVACAO) {
            // Em aprovação → etapa aberta = reprovar (exclusivo do admin).
            $this->exigirAdmin($ator, 'Só o administrador pode reprovar um orçamento em aprovação.');
            $this->exigirTransicao($orc, 'aprovacao_reprovada');

            $orc->update(['status' => 'aprovacao_reprovada', 'funil_etapa_id' => $destino->id]);
            $this->historico($orc, $ator, 'status', "Reprovado na aprovação e devolvido para \"{$destino->nome}\".", 'aprovacao_reprovada');

            return;
        }

        $de = $atual->nome ?? 'Caixa de entrada';
        $orc->update(['funil_etapa_id' => $destino->id]);
        $this->historico($orc, $ator, 'etapa', "Etapa: {$de} → {$destino->nome}.");
    }

    private function enviarParaAprovacao(Orcamento $orc, User $ator): void
    {
        if (! $orc->emNegociacao()) {
            throw new MovimentoInvalido('Só orçamentos em negociação podem ser enviados para aprovação.');
        }
        if ($orc->info?->bloquear_edicao) {
            throw new MovimentoInvalido('Orçamento bloqueado para edição.');
        }
        $this->exigirTransicao($orc, 'aprovando');

        $orc->update(['status' => 'aprovando', 'funil_etapa_id' => $orc->funil_etapa_id ?? $this->primeiraEtapaAberta()->id]);
        $this->historico($orc, $ator, 'status', 'Orçamento enviado para aprovação.', 'aprovando');
    }

    private function aprovar(Orcamento $orc, ?FunilEtapa $atual, User $ator): void
    {
        $this->exigirAdmin($ator, 'Só o administrador aprova orçamentos.');

        if ($atual?->tipo !== FunilEtapa::APROVACAO) {
            throw new MovimentoInvalido('Para fechar a venda, envie o orçamento para aprovação primeiro.');
        }
        $this->exigirTransicao($orc, 'aprovado');

        $orc->update(['status' => 'aprovado']);
        $this->historico($orc, $ator, 'status', 'Orçamento aprovado pelo administrador.', 'aprovado');
    }

    private function reativarDentroDaTransacao(Orcamento $orc, FunilEtapa $destino, User $ator, ?CarbonInterface $proximoContato = null): void
    {
        $orc->update([
            'perdido_em' => null,
            'motivo_perda_id' => null,
            'perda_observacao' => null,
            'funil_etapa_id' => $destino->id,
            'proximo_contato_em' => $proximoContato,
            'tentativas_reativacao' => $orc->tentativas_reativacao + 1,
        ]);
        $this->historico($orc, $ator, 'reativacao', "Negociação reativada em \"{$destino->nome}\" (tentativa {$orc->tentativas_reativacao}).");
    }

    // ── Apoio ─────────────────────────────────────────────────────────────

    /**
     * Executa a operação com o orçamento travado. Com $origem, confere que o card ainda está
     * na coluna que o usuário viu (proteção contra duas pessoas movendo o mesmo card).
     *
     * @param  callable(Orcamento, ?FunilEtapa): void  $operacao
     */
    private function transacao(Orcamento $orcamento, ?string $origem, callable $operacao): void
    {
        DB::transaction(function () use ($orcamento, $origem, $operacao) {
            $orc = Orcamento::with('info')->lockForUpdate()->findOrFail($orcamento->id);
            $atual = $this->etapaDe($orc);

            if ($origem !== null && $origem !== (string) ($atual->id ?? self::CAIXA)) {
                throw new MovimentoInvalido('Este orçamento foi movido por outra pessoa. O quadro foi atualizado.');
            }

            $operacao($orc, $atual);
        });
    }

    private function garantirAbertaAtiva(FunilEtapa $etapa): void
    {
        if ($etapa->tipo !== FunilEtapa::ABERTA || ! $etapa->ativa) {
            throw new MovimentoInvalido('Etapa de destino inválida.');
        }
    }

    private function exigirAdmin(User $ator, string $mensagem): void
    {
        if (! $ator->isAdmin()) {
            throw new MovimentoInvalido($mensagem);
        }
    }

    private function exigirTransicao(Orcamento $orc, string $status): void
    {
        if (! $orc->podeIrPara($status)) {
            throw new MovimentoInvalido("Não é possível mudar de \"{$orc->status}\" para \"{$status}\".");
        }
    }

    private function historico(Orcamento $orc, User $ator, string $tipo, string $mensagem, ?string $status = null): void
    {
        OrcamentoHistorico::create([
            'orcamento_id' => $orc->id,
            'usuario_id' => $ator->id,
            'tipo' => $tipo,
            'status' => $status,
            'mensagem' => $mensagem,
        ]);
    }

    // ── Montagem do quadro ────────────────────────────────────────────────

    /**
     * Dados do quadro para o usuário: colunas com cards, caixa de entrada e totais.
     *
     * Filtros: consultor_id (admin), busca, grupo, atrasados (contato vencido), sem_passo (etapa aberta
     * sem próximo contato), sla (acima do tempo esperado na etapa) e ordem (prioridade | valor | antigo).
     *
     * @param  array{consultor_id?: int|string|null, busca?: string|null, grupo?: string|null, atrasados?: bool, sem_passo?: bool, sla?: bool, ordem?: string|null}  $filtros
     */
    public function quadro(User $usuario, array $filtros = []): array
    {
        $base = fn () => $this->consulta($usuario, $filtros);
        $desde = now()->subDays(self::DIAS_FECHADOS_NO_QUADRO);

        $emNegociacao = $base()->whereNull('perdido_em')->whereIn('status', Orcamento::STATUS_EM_NEGOCIACAO)
            ->where(fn ($q) => $q->whereNotNull('funil_etapa_id')->orWhere('status', 'aprovacao_reprovada'))->get();
        $emAprovacao = $base()->whereNull('perdido_em')->where('status', 'aprovando')->get();
        $ganhos = $base()->whereNull('perdido_em')->whereIn('status', Orcamento::STATUS_GANHO)->where('etapa_entrou_em', '>=', $desde)->get();
        $perdidos = $base()->whereNotNull('perdido_em')->where('perdido_em', '>=', $desde)->get();
        $caixa = $base()->whereNull('perdido_em')->where('status', 'novo')->whereNull('funil_etapa_id')
            ->oldest()->get();

        if ($filtros['sla'] ?? false) {
            // Prazo da etapa depende da coluna, então o filtro é aplicado depois da consulta.
            $emNegociacao = $emNegociacao->filter(fn (Orcamento $o) => $this->slaEstourado($o, $this->etapaDe($o)))->values();
            $emAprovacao = $emAprovacao->filter(fn (Orcamento $o) => $this->slaEstourado($o, $this->etapaSistema(FunilEtapa::APROVACAO)))->values();
            [$ganhos, $perdidos, $caixa] = [new EloquentCollection, new EloquentCollection, new EloquentCollection];
        }

        $porEtapa = $emNegociacao->groupBy(fn (Orcamento $o) => $this->etapaDe($o)?->id);
        $ordem = $filtros['ordem'] ?? null;

        $colunas = $this->etapasAbertas()->map(fn (FunilEtapa $e) => $this->coluna($e, $porEtapa->get($e->id, collect()), $ordem))
            ->push($this->coluna($this->etapaSistema(FunilEtapa::APROVACAO), $emAprovacao, $ordem))
            ->push($this->coluna($this->etapaSistema(FunilEtapa::GANHO), $ganhos, $ordem))
            ->push($this->coluna($this->etapaSistema(FunilEtapa::PERDIDO), $perdidos, $ordem));

        $diasCaixa = (int) Config::get('funil.dias_caixa_entrada', 7);
        $abertos = $emNegociacao->concat($emAprovacao);

        return [
            'colunas' => $colunas->values()->all(),
            'caixa' => $caixa->map(fn (Orcamento $o) => $this->card($o, null) + [
                'esfriando' => $o->created_at->diffInDays(now()) >= $diasCaixa,
            ])->values()->all(),
            'resumo' => [
                'abertos' => $abertos->count(),
                'valor_aberto' => round((float) $abertos->sum('preco_total'), 2),
                'ponderado' => round($colunas->whereIn('tipo', [FunilEtapa::ABERTA, FunilEtapa::APROVACAO])->sum('ponderado'), 2),
                'atrasados' => $abertos->filter(fn (Orcamento $o) => $o->proximo_contato_em?->isPast())->count(),
                'sem_passo' => $emNegociacao->whereNull('proximo_contato_em')->count(),
                'caixa' => $caixa->count(),
                'dias_caixa_entrada' => $diasCaixa,
            ],
        ];
    }

    /** @return Builder<Orcamento> */
    private function consulta(User $usuario, array $filtros): Builder
    {
        return Orcamento::query()
            ->with([
                'cliente:id,tipo_pessoa,nome,razao_social,celular,telefone',
                'cidade:id,cidade,sigla',
                'consultor:id,name',
                'motivoPerda:id,nome,reativavel',
                'contrato:id,orcamento_id,status',
                'itens' => fn ($q) => $q->where('tipo', 'kit')->select('id', 'orcamento_id', 'metadados', 'ordem'),
            ])
            ->when(! $usuario->isAdmin(), fn ($q) => $q->where('consultor_id', $usuario->id))
            ->when($usuario->isAdmin() && ! empty($filtros['consultor_id']), fn ($q) => $q->where('consultor_id', $filtros['consultor_id']))
            ->when($filtros['grupo'] ?? null, fn ($q, $g) => $q->where('grupo_tarifario', $g))
            ->when($filtros['busca'] ?? null, fn ($q, $b) => $this->buscar($q, $b))
            // Atraso de contato só existe em negociação aberta (ganhos e perdidos não têm follow-up).
            ->when($filtros['atrasados'] ?? false, fn ($q) => $q->where('proximo_contato_em', '<', now())
                ->whereNull('perdido_em')->whereNotIn('status', Orcamento::STATUS_GANHO))
            // Sem próximo passo: negociação numa etapa aberta (fora da caixa) sem contato agendado.
            ->when($filtros['sem_passo'] ?? false, fn ($q) => $q->whereNull('proximo_contato_em')->whereNull('perdido_em')
                ->whereIn('status', Orcamento::STATUS_EM_NEGOCIACAO)
                ->where(fn ($q) => $q->whereNotNull('funil_etapa_id')->orWhere('status', 'aprovacao_reprovada')));
    }

    /**
     * Busca por número do orçamento (só "123" ou "#123") ou por nome/razão social do cliente.
     *
     * @param  Builder<Orcamento>  $query
     */
    private function buscar(Builder $query, string $busca): void
    {
        $numero = ltrim($busca, '#');
        $like = "%{$busca}%";

        $query->where(fn ($q) => $q
            ->when(ctype_digit($numero), fn ($q) => $q->where('id', (int) $numero))
            ->orWhereHas('cliente', fn ($q) => $q->withTrashed()->where(fn ($q) => $q
                ->where('nome', 'like', $like)->orWhere('razao_social', 'like', $like))));
    }

    /** @param  Collection<int, Orcamento>  $orcamentos */
    private function coluna(FunilEtapa $etapa, Collection $orcamentos, ?string $ordem = null): array
    {
        $valor = (float) $orcamentos->sum('preco_total');
        $maisAntigo = fn (Orcamento $a, Orcamento $b) => ($a->etapa_entrou_em->timestamp ?? 0) <=> ($b->etapa_entrou_em->timestamp ?? 0);

        $ordenados = $orcamentos->sortBy(match ($ordem) {
            'valor' => [fn (Orcamento $a, Orcamento $b) => (float) $b->preco_total <=> (float) $a->preco_total, $maisAntigo],
            'antigo' => [$maisAntigo],
            // Prioridade (padrão): contato atrasado → próximo contato mais cedo → há mais tempo parado.
            default => [
                fn (Orcamento $a, Orcamento $b) => (int) ! $a->proximo_contato_em?->isPast() <=> (int) ! $b->proximo_contato_em?->isPast(),
                fn (Orcamento $a, Orcamento $b) => ($a->proximo_contato_em->timestamp ?? PHP_INT_MAX) <=> ($b->proximo_contato_em->timestamp ?? PHP_INT_MAX),
                $maisAntigo,
            ],
        });

        return [
            'id' => $etapa->id,
            'nome' => $etapa->nome,
            'cor' => $etapa->cor,
            'tipo' => $etapa->tipo,
            'probabilidade' => $etapa->probabilidade,
            'sla_dias' => $etapa->sla_dias,
            'quantidade' => $orcamentos->count(),
            'valor' => round($valor, 2),
            'ponderado' => round($valor * ($etapa->probabilidade ?? 0) / 100, 2),
            'cards' => $ordenados->take(self::LIMITE_POR_COLUNA)->map(fn (Orcamento $o) => $this->card($o, $etapa))->values()->all(),
        ];
    }

    /** Dias na coluna atual (na caixa, desde a criação; em Perdido, desde a perda). */
    private function diasNaEtapa(Orcamento $o, ?FunilEtapa $etapa): int
    {
        $referencia = $etapa?->tipo === FunilEtapa::PERDIDO ? $o->perdido_em : ($etapa ? $o->etapa_entrou_em : $o->created_at);

        return (int) ($referencia ?? $o->created_at)->diffInDays(now());
    }

    private function slaEstourado(Orcamento $o, ?FunilEtapa $etapa): bool
    {
        return in_array($etapa?->tipo, [FunilEtapa::ABERTA, FunilEtapa::APROVACAO], true)
            && $etapa->sla_dias !== null && $this->diasNaEtapa($o, $etapa) > $etapa->sla_dias;
    }

    /**
     * Telefone para "Ligar" e número internacional para o WhatsApp (wa.me). Prefere o celular;
     * números brasileiros sem DDI recebem 55.
     *
     * @return array{telefone: string|null, whatsapp: string|null}
     */
    public static function contatos(?Cliente $cliente): array
    {
        $digitos = preg_replace('/\D/', '', (string) ($cliente?->celular ?: $cliente?->telefone));

        if (strlen($digitos) < 10) {
            return ['telefone' => null, 'whatsapp' => null];
        }

        return ['telefone' => $digitos, 'whatsapp' => strlen($digitos) <= 11 ? "55{$digitos}" : $digitos];
    }

    private function card(Orcamento $o, ?FunilEtapa $etapa): array
    {
        $dias = $this->diasNaEtapa($o, $etapa);
        $acompanhado = $etapa === null || in_array($etapa->tipo, [FunilEtapa::ABERTA, FunilEtapa::APROVACAO], true);
        $slaEstourado = $this->slaEstourado($o, $etapa);
        $contatoAtrasado = $acompanhado && (bool) $o->proximo_contato_em?->isPast();

        return [
            'id' => $o->id,
            'cliente' => $o->cliente->nome_display ?? 'Cliente removido',
            'cidade' => $o->cidade ? "{$o->cidade->cidade}/{$o->cidade->sigla}" : null,
            'valor' => (float) $o->preco_total,
            'potencia_kwp' => round((float) $o->itens->sum(fn ($i) => (float) ($i->metadados['potencia_kwp'] ?? 0)), 2) ?: null,
            'grupo' => $o->grupo_tarifario,
            'status' => $o->status,
            'consultor' => $o->consultor ? ['id' => $o->consultor->id, 'nome' => $o->consultor->name] : null,
            'dias_na_etapa' => $dias,
            'sla_estourado' => $slaEstourado,
            'proximo_contato_em' => $o->proximo_contato_em?->toIso8601String(),
            'contato_atrasado' => $contatoAtrasado,
            'saude' => $this->saude($o, $etapa, $dias, $slaEstourado, $contatoAtrasado),
            'reprovado' => $o->status === 'aprovacao_reprovada',
            'tem_contrato' => $o->contrato !== null,
            'motivo_perda' => $o->motivoPerda?->nome,
            'reativavel' => (bool) $o->motivoPerda?->reativavel,
            'tentativas_reativacao' => $o->tentativas_reativacao,
            ...self::contatos($o->cliente),
        ];
    }

    /**
     * Indicador único de saúde do card (docs/funil-de-vendas.md, seção 22.3). Null = coluna
     * sem acompanhamento comercial (Ganho, Perdido) ou caixa de entrada, que usa o selo "esfriando".
     */
    private function saude(Orcamento $o, ?FunilEtapa $etapa, int $dias, bool $slaEstourado, bool $contatoAtrasado): ?string
    {
        if (! in_array($etapa?->tipo, [FunilEtapa::ABERTA, FunilEtapa::APROVACAO], true)) {
            return null;
        }

        if ($contatoAtrasado || $slaEstourado) {
            return self::SAUDE_ATRASADO;
        }

        $fuso = config('app.timezone_exibicao');
        $contatoHoje = $o->proximo_contato_em?->copy()->setTimezone($fuso)->isSameDay(now($fuso));
        if ($contatoHoje || ($etapa->sla_dias && $dias * 10 >= $etapa->sla_dias * 7)) {
            return self::SAUDE_ATENCAO;
        }

        return $etapa->tipo === FunilEtapa::ABERTA && $o->proximo_contato_em === null ? self::SAUDE_SEM_PASSO : self::SAUDE_EM_DIA;
    }
}
