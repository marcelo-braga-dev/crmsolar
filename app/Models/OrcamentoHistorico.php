<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrcamentoHistorico extends Model
{
    protected $table = 'orcamento_historicos';

    protected $fillable = ['orcamento_id', 'usuario_id', 'status', 'mensagem'];

    public function orcamento(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
