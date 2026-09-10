<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MargemFornecedor extends Model
{
    protected $table = 'margens_fornecedores';

    protected $fillable = ['fornecedor_id', 'margem'];

    protected $casts = ['margem' => 'decimal:3'];

    public function fornecedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
