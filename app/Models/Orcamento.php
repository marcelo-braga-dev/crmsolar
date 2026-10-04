<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Orcamento extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'consultor_id', 'cliente_id', 'cidade_id',
        'status', 'grupo_tarifario', 'modalidade_tarifaria',
        'preco_total', 'geracao_estimada',
        'token', 'anotacoes',
        // Funil de vendas (docs/funil-de-vendas.md)
        'funil_etapa_id', 'etapa_entrou_em', 'proximo_contato_em',
        'perdido_em', 'motivo_perda_id', 'perda_observacao', 'tentativas_reativacao',
    ];

    protected $casts = [
        'preco_total' => 'decimal:2',
        'etapa_entrou_em' => 'datetime',
        'proximo_contato_em' => 'datetime',
        'perdido_em' => 'datetime',
        'tentativas_reativacao' => 'integer',
    ];

    /** Status em que a negociação ainda está aberta (antes de enviar para aprovação). */
    public const STATUS_EM_NEGOCIACAO = ['novo', 'aprovacao_reprovada'];

    /** Status que contam como venda fechada (coluna Ganho, faturamento, comissões). */
    public const STATUS_GANHO = ['aprovado', 'instalando', 'finalizado'];

    /**
     * Transições de status permitidas (de → para). Fonte única para Admin e Consultor.
     * Inclui um passo de volta para correções (ex.: aprovado → aprovando).
     */
    public const TRANSICOES = [
        'novo' => ['aprovando'],
        'aprovando' => ['aprovado', 'aprovacao_reprovada', 'novo'],
        'aprovacao_reprovada' => ['aprovando', 'novo'],
        'aprovado' => ['instalando', 'aprovando'],
        'instalando' => ['finalizado', 'aprovado'],
        'finalizado' => [],
    ];

    /** Status anteriores à aprovação — reabrem o orçamento para edição de itens e preço. */
    public const STATUS_PRE_APROVACAO = ['novo', 'aprovando', 'aprovacao_reprovada'];

    public function podeIrPara(string $status): bool
    {
        return in_array($status, $this->transicoesPermitidas(), true);
    }

    /**
     * Destinos de TRANSICOES válidos para este orçamento. Com contrato vigente (não cancelado),
     * não volta para antes da aprovação: o preço mudaria e deixaria de bater com o contrato.
     *
     * @return array<int, string>
     */
    public function transicoesPermitidas(): array
    {
        $destinos = self::TRANSICOES[$this->status];

        if ($this->contrato()->where('status', '!=', 'cancelado')->exists()) {
            $destinos = array_values(array_diff($destinos, self::STATUS_PRE_APROVACAO));
        }

        return $destinos;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $orcamento) {
            if (empty($orcamento->token)) {
                $orcamento->token = Str::random(60);
            }
            $orcamento->etapa_entrou_em ??= now();
        });

        // Aging do funil: o relógio da etapa só reinicia quando o card muda de coluna,
        // seja pelo quadro ou pela tela do orçamento (aprovação, reprovação, perda).
        static::updating(function (self $orcamento) {
            $mudouColuna = $orcamento->isDirty(['funil_etapa_id', 'perdido_em'])
                || ($orcamento->isDirty('status')
                    && self::grupoDoStatus((string) $orcamento->getOriginal('status')) !== self::grupoDoStatus((string) $orcamento->status));

            if ($mudouColuna && ! $orcamento->isDirty('etapa_entrou_em')) {
                $orcamento->etapa_entrou_em = now();
            }
        });
    }

    /** Agrupa o status operacional nas colunas que ele determina no funil. */
    public static function grupoDoStatus(string $status): string
    {
        return match (true) {
            in_array($status, self::STATUS_GANHO, true) => FunilEtapa::GANHO,
            $status === 'aprovando' => FunilEtapa::APROVACAO,
            default => FunilEtapa::ABERTA,
        };
    }

    public function estaPerdido(): bool
    {
        return $this->perdido_em !== null;
    }

    public function emNegociacao(): bool
    {
        return ! $this->estaPerdido() && in_array($this->status, self::STATUS_EM_NEGOCIACAO, true);
    }

    /** @return BelongsTo<FunilEtapa, $this> */
    public function funilEtapa(): BelongsTo
    {
        return $this->belongsTo(FunilEtapa::class, 'funil_etapa_id');
    }

    /** @return BelongsTo<MotivoPerda, $this> */
    public function motivoPerda(): BelongsTo
    {
        return $this->belongsTo(MotivoPerda::class, 'motivo_perda_id');
    }

    /** @return BelongsTo<User, $this> */
    public function consultor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    /**
     * Inclui clientes excluídos (soft delete): o registro continua mostrando de quem é.
     *
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    /** @return BelongsTo<CidadeEstado, $this> */
    public function cidade(): BelongsTo
    {
        return $this->belongsTo(CidadeEstado::class, 'cidade_id');
    }

    /** @return HasOne<OrcamentoInfo, $this> */
    public function info(): HasOne
    {
        return $this->hasOne(OrcamentoInfo::class);
    }

    /** @return HasMany<OrcamentoItem, $this> */
    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('ordem');
    }

    /** @return HasMany<OrcamentoHistorico, $this> */
    public function historicos(): HasMany
    {
        return $this->hasMany(OrcamentoHistorico::class)->latest();
    }

    /** @return HasOne<OrcamentoAprovacao, $this> */
    public function aprovacao(): HasOne
    {
        return $this->hasOne(OrcamentoAprovacao::class);
    }

    /** @return HasOne<OrcamentoVistoria, $this> */
    public function vistoria(): HasOne
    {
        return $this->hasOne(OrcamentoVistoria::class);
    }

    /** @return HasOne<Contrato, $this> */
    public function contrato(): HasOne
    {
        return $this->hasOne(Contrato::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
