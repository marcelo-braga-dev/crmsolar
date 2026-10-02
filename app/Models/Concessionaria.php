<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Concessionaria extends Model
{
    use LogsActivity;

    protected $table = 'concessionarias';

    protected $fillable = [
        'nome', 'estado',
        'tarifa_convencional', 'tarifa_ponta',
        'tarifa_intermediaria', 'tarifa_fora_ponta',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'tarifa_convencional' => 'decimal:5',
        'tarifa_ponta' => 'decimal:5',
        'tarifa_intermediaria' => 'decimal:5',
        'tarifa_fora_ponta' => 'decimal:5',
    ];

    /** Tarifas usadas na análise econômica. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
