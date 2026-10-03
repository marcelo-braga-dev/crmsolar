<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Lead extends Model
{
    use LogsActivity;

    protected $fillable = [
        'consultor_id', 'nome', 'email', 'telefone',
        'cidade', 'estado', 'consumo_mensal',
        'origem', 'dados_extras', 'status', 'anotacoes',
    ];

    protected $casts = [
        'dados_extras' => 'array',
        'consumo_mensal' => 'decimal:2',
    ];

    /** @return BelongsTo<User, $this> */
    public function consultor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
