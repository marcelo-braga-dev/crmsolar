<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tipo',
        'status',
        'cpf',
        'rg',
        'cnpj',
        'celular',
        'comissao_percentual',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Espelha o default da coluna — sem isso, um User recém-criado tem status null até ser recarregado.
    protected $attributes = [
        'status' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'status'              => 'boolean',
            'comissao_percentual' => 'decimal:2',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->tipo === 'admin';
    }

    public function isConsultor(): bool
    {
        return $this->tipo === 'consultor';
    }

    public function clientes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cliente::class, 'consultor_id');
    }

    public function orcamentos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Orcamento::class, 'consultor_id');
    }
}
