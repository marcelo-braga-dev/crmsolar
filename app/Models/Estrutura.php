<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estrutura extends Model
{
    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function kits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Kit::class);
    }

    public function margemPrecificacao(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MargemEstrutura::class);
    }
}
