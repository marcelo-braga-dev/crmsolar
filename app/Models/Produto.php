<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $fillable = [
        'categoria_id', 'marca_id', 'fornecedor_id',
        'nome', 'modelo', 'sku', 'descricao',
        'potencia', 'unidade_potencia', 'tensao', 'unidade',
        'preco_custo', 'garantia', 'imagem_url', 'ficha_tecnica_url',
        'atributos', 'ativo', 'ativo_fornecedor',
    ];

    protected $casts = [
        'atributos' => 'array',
        'ativo' => 'boolean',
        'ativo_fornecedor' => 'boolean',
        'preco_custo' => 'decimal:2',
        'potencia' => 'decimal:3',
    ];

    public function categoria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CategoriaProduto::class, 'categoria_id');
    }

    public function marca(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function fornecedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function kits(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Kit::class, 'kit_componentes')
            ->withPivot('quantidade', 'observacao');
    }
}
