<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'consultor_id', 'nome', 'email', 'telefone',
        'cidade', 'estado', 'consumo_mensal',
        'origem', 'dados_extras', 'status', 'anotacoes',
    ];

    protected $casts = [
        'dados_extras' => 'array',
        'consumo_mensal' => 'decimal:2',
    ];

    public function consultor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }
}
