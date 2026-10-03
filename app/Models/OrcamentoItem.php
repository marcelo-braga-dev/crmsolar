<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class OrcamentoItem extends Model
{
    use LogsActivity;

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

    /** @return BelongsTo<Orcamento, $this> */
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    /** @return BelongsTo<Kit, $this> */
    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    /** @return BelongsTo<Produto, $this> */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    /** Preços e itens adicionados/removidos do orçamento. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
