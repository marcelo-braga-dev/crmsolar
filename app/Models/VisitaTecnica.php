<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class VisitaTecnica extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'visitas_tecnicas';

    protected $fillable = [
        'consultor_id', 'cliente_id', 'orcamento_id',
        'data_agendada', 'status', 'anotacoes',
    ];

    protected $casts = [
        'data_agendada' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function consultor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    /**
     * Inclui clientes excluídos (soft delete): o registro continua mostrando de quem é.
     *
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    /** @return BelongsTo<Orcamento, $this> */
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
