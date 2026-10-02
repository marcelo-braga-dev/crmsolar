<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoAprovacao extends Model
{
    protected $table = 'orcamento_aprovacoes';

    protected $fillable = [
        'orcamento_id', 'nome_assinante', 'documento_assinante',
        'ip_assinatura', 'forma_pagamento', 'observacoes', 'assinado_em',
    ];

    protected $casts = [
        'assinado_em' => 'datetime',
    ];

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }
}
