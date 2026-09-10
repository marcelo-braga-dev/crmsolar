<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Orcamento extends Model
{
    protected $fillable = [
        'consultor_id', 'cliente_id', 'cidade_id',
        'status', 'grupo_tarifario', 'modalidade_tarifaria',
        'preco_total', 'geracao_estimada',
        'token', 'anotacoes',
    ];

    protected $casts = [
        'preco_total' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $orcamento) {
            if (empty($orcamento->token)) {
                $orcamento->token = Str::random(60);
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

    public function cidade(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CidadeEstado::class, 'cidade_id');
    }

    public function info(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrcamentoInfo::class);
    }

    public function itens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('ordem');
    }

    public function historicos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrcamentoHistorico::class)->latest();
    }

    public function aprovacao(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrcamentoAprovacao::class);
    }

    public function vistoria(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrcamentoVistoria::class);
    }

    public function contrato(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Contrato::class);
    }
}
