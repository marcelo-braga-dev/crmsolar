<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Kit extends Model
{
    use LogsActivity;

    /** Só mudanças de preço/disponibilidade — a sincronização cria milhares de kits. */
    protected static array $recordEvents = ['updated', 'deleted'];

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

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function estrutura(): BelongsTo
    {
        return $this->belongsTo(Estrutura::class);
    }

    public function componentes(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'kit_componentes')
            ->withPivot('quantidade', 'observacao');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['nome', 'preco_custo', 'margem_padrao', 'ativo', 'ativo_fornecedor'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
