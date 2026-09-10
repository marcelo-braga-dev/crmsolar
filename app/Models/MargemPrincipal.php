<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MargemPrincipal extends Model
{
    protected $table = 'margens_principal';

    protected $fillable = ['nome', 'potencia_min', 'potencia_max', 'margem', 'ordem'];

    protected $casts = [
        'potencia_min' => 'decimal:3',
        'potencia_max' => 'decimal:3',
        'margem' => 'decimal:3',
    ];
}
