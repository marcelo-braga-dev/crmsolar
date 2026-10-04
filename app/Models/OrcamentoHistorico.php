<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoHistorico extends Model
{
    protected $table = 'orcamento_historicos';

    /** Tipos de evento da linha do tempo (o funil registra eventos que não mudam o status). */
    public const TIPOS = ['status', 'etapa', 'contato', 'perda', 'reativacao'];

    protected $fillable = ['orcamento_id', 'usuario_id', 'tipo', 'status', 'mensagem'];

    /** Espelha o default da coluna: entradas antigas e criadas sem tipo são de status. */
    protected $attributes = ['tipo' => 'status'];

    /** @return BelongsTo<Orcamento, $this> */
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
