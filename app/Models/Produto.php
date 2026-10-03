<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Produto extends Model
{
    use LogsActivity;

    /** Só mudanças de preço/disponibilidade — a sincronização cria milhares de produtos. */
    protected static array $recordEvents = ['updated', 'deleted'];

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

    /** @return BelongsTo<CategoriaProduto, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProduto::class, 'categoria_id');
    }

    /** @return BelongsTo<Marca, $this> */
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /** @return BelongsTo<Fornecedor, $this> */
    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    /** @return BelongsToMany<Kit, $this> */
    public function kits(): BelongsToMany
    {
        return $this->belongsToMany(Kit::class, 'kit_componentes')
            ->withPivot('quantidade', 'observacao');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['nome', 'preco_custo', 'ativo', 'ativo_fornecedor'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
