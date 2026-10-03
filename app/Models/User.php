<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use LogsActivity;

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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
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

    /** @return HasMany<Cliente, $this> */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'consultor_id');
    }

    /** @return HasMany<Orcamento, $this> */
    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class, 'consultor_id');
    }

    /** Nunca registra senha; troca só de senha não gera log. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email', 'tipo', 'status', 'comissao_percentual'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
