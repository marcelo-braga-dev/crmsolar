<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estrutura extends Model
{
    public $timestamps = false;

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function kits(): HasMany
    {
        return $this->hasMany(Kit::class);
    }
}
