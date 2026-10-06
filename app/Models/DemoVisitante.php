<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Visitante do modo demonstração (lead para a equipe comercial). */
class DemoVisitante extends Model
{
    protected $table = 'demo_visitantes';

    protected $fillable = [
        'nome', 'email', 'telefone', 'empresa', 'ip', 'user_agent', 'referer',
        'utm_source', 'utm_medium', 'utm_campaign', 'visitas', 'telas_vistas',
        'perfis_vistos', 'ultimo_perfil', 'primeiro_acesso_em', 'ultimo_acesso_em',
    ];

    protected $casts = [
        'perfis_vistos' => 'array',
        'visitas' => 'integer',
        'telas_vistas' => 'integer',
        'primeiro_acesso_em' => 'datetime',
        'ultimo_acesso_em' => 'datetime',
    ];
}
