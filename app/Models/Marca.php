<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    protected $fillable = ['nome', 'url_logo', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function produtos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Produto::class);
    }
}
