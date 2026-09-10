<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kit extends Model
{
    protected $fillable = [
        'fornecedor_id', 'estrutura_id',
        'nome', 'modelo', 'sku', 'categoria',
        'potencia_kwp', 'tensao', 'inclui_trafo',
        'preco_custo', 'margem_padrao',
        'ativo', 'ativo_fornecedor', 'observacoes',
    ];

    protected $casts = [
        'inclui_trafo' => 'boolean',
        'ativo' => 'boolean',
        'ativo_fornecedor' => 'boolean',
        'preco_custo' => 'decimal:2',
        'margem_padrao' => 'decimal:3',
        'potencia_kwp' => 'decimal:3',
    ];

    public function fornecedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function estrutura(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Estrutura::class);
    }

    public function componentes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'kit_componentes')
            ->withPivot('quantidade', 'observacao');
    }
}
