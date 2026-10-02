<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoItem extends Model
{
    protected $table = 'orcamento_itens';

    protected $fillable = [
        'orcamento_id', 'tipo', 'kit_id', 'produto_id',
        'descricao', 'quantidade',
        'preco_custo_unitario', 'preco_venda_unitario', 'preco_venda_total',
        'margem_percentual', 'comissao_percentual',
        'geracao_estimada', 'metadados', 'ordem',
    ];

    protected $casts = [
        'metadados' => 'array',
        'preco_custo_unitario' => 'decimal:2',
        'preco_venda_unitario' => 'decimal:2',
        'preco_venda_total' => 'decimal:2',
        'margem_percentual' => 'decimal:3',
        'comissao_percentual' => 'decimal:3',
    ];

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
