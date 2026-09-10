<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CidadeEstado extends Model
{
    public $timestamps = false;

    protected $table = 'cidades_estados';

    protected $fillable = ['cidade', 'estado'];

    public function irradiacaoSolar(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(IrradiacaoSolar::class, 'cidade_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->cidade} - {$this->estado}";
    }
}
