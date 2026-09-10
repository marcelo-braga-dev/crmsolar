<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MargemEstrutura extends Model
{
    protected $table = 'margens_estruturas';

    protected $fillable = ['estrutura_id', 'margem'];

    protected $casts = ['margem' => 'decimal:3'];

    public function estrutura(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Estrutura::class);
    }
}
