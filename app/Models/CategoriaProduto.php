<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaProduto extends Model
{
    protected $table = 'categorias_produtos';

    protected $fillable = ['nome', 'slug', 'descricao', 'icone', 'eh_componente_kit', 'exige_potencia', 'ativo', 'ordem'];

    protected $casts = [
        'ativo'             => 'boolean',
        'eh_componente_kit' => 'boolean',
        'exige_potencia'    => 'boolean',
    ];

    public function produtos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Produto::class, 'categoria_id');
    }
}
