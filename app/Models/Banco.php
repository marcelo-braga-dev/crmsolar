<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $fillable = [
        'nome', 'juros_mensal', 'qtd_parcelas', 'carencia', 'ativo', 'url_logo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'juros_mensal' => 'decimal:4',
    ];
}
