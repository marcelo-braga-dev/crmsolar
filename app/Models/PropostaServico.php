<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PropostaServico extends Model
{
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
        'valor'       => 'decimal:2',
        'validade'    => 'date',
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

    public function consultor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'consultor_id');
    }

    public function cliente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}