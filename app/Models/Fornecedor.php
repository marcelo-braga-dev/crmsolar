<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fornecedor extends Model
{
    protected $table = 'fornecedores';

    protected $fillable = [
        'nome', 'cnpj', 'email', 'telefone', 'celular',
        'representante', 'site', 'margem_padrao', 'anotacoes', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'margem_padrao' => 'decimal:2',
    ];

    public function kits(): HasMany
    {
        return $this->hasMany(Kit::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    public function margemPrecificacao(): HasOne
    {
        return $this->hasOne(MargemFornecedor::class);
    }
}
