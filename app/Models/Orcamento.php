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
    ];

    protected $casts = [
        'preco_total' => 'decimal:2',
    ];

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

    public function podeIrPara(string $status): bool
    {
        return in_array($status, self::TRANSICOES[$this->status] ?? [], true);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $orcamento) {
            if (empty($orcamento->token)) {
                $orcamento->token = Str::random(60);
            }
        });
    }

    public function consultor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(CidadeEstado::class, 'cidade_id');
    }

    public function info(): HasOne
    {
        return $this->hasOne(OrcamentoInfo::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('ordem');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(OrcamentoHistorico::class)->latest();
    }

    public function aprovacao(): HasOne
    {
        return $this->hasOne(OrcamentoAprovacao::class);
    }

    public function vistoria(): HasOne
    {
        return $this->hasOne(OrcamentoVistoria::class);
    }

    public function contrato(): HasOne
    {
        return $this->hasOne(Contrato::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
