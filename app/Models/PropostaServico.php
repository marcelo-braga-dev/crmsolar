<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PropostaServico extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'proposta_servicos';

    protected $fillable = [
        'consultor_id',
        'cliente_id',
        'titulo',
        'descricao',
        'conteudo',
        'valor',
        'validade',
        'prazo_final',
        'observacoes',
        'status',
        'token',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'validade' => 'date',
        'prazo_final' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $p) {
            if (empty($p->token)) {
                $p->token = Str::random(48);
            }
        });
    }

    public function consultor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
