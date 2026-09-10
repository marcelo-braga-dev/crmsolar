<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'consultor_id', 'cidade_id',
        'tipo_pessoa', 'nome', 'razao_social',
        'cpf', 'cnpj', 'rg', 'data_nascimento',
        'email', 'telefone', 'celular',
        'cep', 'rua', 'numero', 'complemento', 'bairro',
        'status', 'anotacoes',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    public function consultor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    public function cidade(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CidadeEstado::class, 'cidade_id');
    }

    public function orcamentos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Orcamento::class);
    }

    public function visitas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VisitaTecnica::class);
    }

    public function getNomeDisplayAttribute(): string
    {
        return $this->tipo_pessoa === 'pj'
            ? ($this->razao_social ?? 'Sem nome')
            : ($this->nome ?? 'Sem nome');
    }
}
