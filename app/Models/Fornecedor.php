<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function kits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Kit::class);
    }

    public function produtos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Produto::class);
    }

    public function margemPrecificacao(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MargemFornecedor::class);
    }
}
