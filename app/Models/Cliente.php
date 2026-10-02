<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Cliente extends Model
{
    use LogsActivity, SoftDeletes;

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

    public function consultor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(CidadeEstado::class, 'cidade_id');
    }

    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class);
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(VisitaTecnica::class);
    }

    public function getNomeDisplayAttribute(): string
    {
        return $this->tipo_pessoa === 'pj'
            ? ($this->razao_social ?? 'Sem nome')
            : ($this->nome ?? 'Sem nome');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
