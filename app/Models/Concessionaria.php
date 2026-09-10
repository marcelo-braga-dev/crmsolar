<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concessionaria extends Model
{
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
}
