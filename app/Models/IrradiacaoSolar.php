<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrradiacaoSolar extends Model
{
    public $timestamps = false;

    protected $table = 'irradiacao_solar';

    protected $fillable = [
        'cidade_id', 'media',
        'jan', 'fev', 'mar', 'abr', 'mai', 'jun',
        'jul', 'ago', 'set', 'out', 'nov', 'dez',
    ];

    protected $casts = [
        'media' => 'decimal:3',
        'jan' => 'decimal:3', 'fev' => 'decimal:3', 'mar' => 'decimal:3',
        'abr' => 'decimal:3', 'mai' => 'decimal:3', 'jun' => 'decimal:3',
        'jul' => 'decimal:3', 'ago' => 'decimal:3', 'set' => 'decimal:3',
        'out' => 'decimal:3', 'nov' => 'decimal:3', 'dez' => 'decimal:3',
    ];

    public function cidade(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CidadeEstado::class, 'cidade_id');
    }
}
