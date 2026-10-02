<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MargemFornecedor extends Model
{
    protected $table = 'margens_fornecedores';

    protected $fillable = ['fornecedor_id', 'margem'];

    protected $casts = ['margem' => 'decimal:3'];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
