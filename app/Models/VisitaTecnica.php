<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitaTecnica extends Model
{
    protected $table = 'visitas_tecnicas';

    protected $fillable = [
        'consultor_id', 'cliente_id', 'orcamento_id',
        'data_agendada', 'status', 'anotacoes',
    ];

    protected $casts = [
        'data_agendada' => 'datetime',
    ];

    public function consultor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    public function cliente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function orcamento(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }
}
