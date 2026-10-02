<?php

namespace App\Policies;

use App\Models\Contrato;
use App\Models\User;

class ContratoPolicy
{
    public function view(User $user, Contrato $contrato): bool
    {
        return $contrato->consultor_id === $user->id;
    }
}
