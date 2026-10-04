<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class MotivoPerda extends Model
{
    use LogsActivity;

    protected $table = 'motivos_perda';

    protected $fillable = ['nome', 'reativavel', 'reativar_apos_dias', 'ordem', 'ativo'];

    protected $casts = [
        'reativavel' => 'boolean',
        'ativo' => 'boolean',
        'ordem' => 'integer',
        'reativar_apos_dias' => 'integer',
    ];

    /** @return HasMany<Orcamento, $this> */
    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class, 'motivo_perda_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
