<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegracaoHistorico extends Model
{
    protected $table = 'integracao_historicos';

    protected $fillable = [
        'fornecedor_id', 'tipo', 'status', 'alertas',
        'itens_importados', 'itens_atualizados', 'itens_desativados',
        'detalhes', 'iniciado_em', 'finalizado_em',
    ];

    protected $casts = [
        'detalhes' => 'array',
        'iniciado_em' => 'datetime',
        'finalizado_em' => 'datetime',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
