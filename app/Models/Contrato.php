<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $fillable = [
        'orcamento_id', 'consultor_id',
        'nome_cliente', 'documento_cliente', 'endereco_instalacao',
        'potencia_kwp', 'qtd_paineis', 'qtd_inversores',
        'modelo_inversor', 'consumo_mensal', 'geracao_estimada',
        'garantia_paineis', 'garantia_inversores',
        'valor_total', 'formas_pagamento',
        'clausulas_adicionais', 'produtos_snapshot', 'status',
    ];

    protected $casts = [
        'produtos_snapshot' => 'array',
        'valor_total' => 'decimal:2',
        'potencia_kwp' => 'decimal:3',
    ];

    public function orcamento(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function consultor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }
}
