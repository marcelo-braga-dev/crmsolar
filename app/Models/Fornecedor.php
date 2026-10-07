<?php

namespace App\Models;

use App\Services\Integracoes\NomeDistribuidora;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fornecedor extends Model
{
    protected $table = 'fornecedores';

    protected $fillable = [
        'nome', 'integracao', 'cnpj', 'email', 'telefone', 'celular',
        'representante', 'site', 'margem_padrao', 'anotacoes', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'margem_padrao' => 'decimal:2',
    ];

    /**
     * Valor de `integracao` do fornecedor do catálogo integrado. Genérico de propósito: o fornecedor
     * vai para o navegador e, na demonstração, o nome real da distribuidora é confidencial.
     */
    public const INTEGRACAO_DISTRIBUIDORA = 'distribuidora';

    /**
     * Fornecedor ligado à integração: pela coluna `integracao`; sem ela, pelo nome (cadastro manual antigo).
     *
     * @param  Builder<Fornecedor>  $query
     * @return Builder<Fornecedor>
     */
    public function scopeDaIntegracao(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('integracao', self::INTEGRACAO_DISTRIBUIDORA)
            ->orWhere(fn ($q) => $q->whereNull('integracao')->where('nome', 'like', '%'.NomeDistribuidora::REAL.'%')))
            ->orderByRaw('integracao is null')
            ->orderBy('id');
    }

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
