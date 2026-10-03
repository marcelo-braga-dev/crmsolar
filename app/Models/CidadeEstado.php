<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CidadeEstado extends Model
{
    public $timestamps = false;

    protected $table = 'cidades_estados';

    protected $fillable = ['cidade', 'estado', 'sigla'];

    /** @return HasOne<IrradiacaoSolar, $this> */
    public function irradiacaoSolar(): HasOne
    {
        return $this->hasOne(IrradiacaoSolar::class, 'cidade_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->cidade} - {$this->estado}";
    }
}
