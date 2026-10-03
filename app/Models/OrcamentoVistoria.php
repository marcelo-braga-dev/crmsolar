<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoVistoria extends Model
{
    protected $table = 'orcamento_vistorias';

    protected $fillable = [
        'orcamento_id',
        'largura_telhado', 'altura_telhado',
        'tipo_disjuntor', 'padrao_energia', 'tipo_telhado',
        'tipo_fiacao', 'tipo_medidor', 'outros', 'observacoes', 'imagens',
    ];

    protected $casts = [
        'imagens' => 'array',
        'largura_telhado' => 'decimal:2',
        'altura_telhado' => 'decimal:2',
    ];

    /** @return BelongsTo<Orcamento, $this> */
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }
}
