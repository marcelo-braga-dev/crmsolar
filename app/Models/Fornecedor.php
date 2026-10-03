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

    /** @return HasMany<Kit, $this> */
    public function kits(): HasMany
    {
        return $this->hasMany(Kit::class);
    }

    /** @return HasMany<Produto, $this> */
    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    /** @return HasOne<MargemFornecedor, $this> */
    public function margemPrecificacao(): HasOne
    {
        return $this->hasOne(MargemFornecedor::class);
    }
}
