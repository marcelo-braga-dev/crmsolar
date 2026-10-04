<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Coluna do funil de vendas. O comportamento vem do tipo (imutável); nome, cor e
 * parâmetros são editáveis. Ver docs/funil-de-vendas.md.
 */
class FunilEtapa extends Model
{
    use LogsActivity;

    public const ABERTA = 'aberta';

    public const APROVACAO = 'aprovacao';

    public const GANHO = 'ganho';

    public const PERDIDO = 'perdido';

    /** Etapas de sistema: exatamente uma de cada, não podem ser criadas nem excluídas. */
    public const TIPOS_SISTEMA = [self::APROVACAO, self::GANHO, self::PERDIDO];

    protected $table = 'funil_etapas';

    protected $fillable = ['nome', 'cor', 'tipo', 'ordem', 'probabilidade', 'sla_dias', 'ativa'];

    protected $casts = [
        'ativa' => 'boolean',
        'ordem' => 'integer',
        'probabilidade' => 'integer',
        'sla_dias' => 'integer',
    ];

    public function ehSistema(): bool
    {
        return in_array($this->tipo, self::TIPOS_SISTEMA, true);
    }

    /** @param  Builder<FunilEtapa>  $query */
    public function scopeAbertas(Builder $query): void
    {
        $query->where('tipo', self::ABERTA)->where('ativa', true)->orderBy('ordem')->orderBy('id');
    }

    /** @return HasMany<Orcamento, $this> */
    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class, 'funil_etapa_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
