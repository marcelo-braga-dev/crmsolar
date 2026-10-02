<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function view(User $user, Cliente $cliente): bool
    {
        return $cliente->consultor_id === $user->id;
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $cliente->consultor_id === $user->id;
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $cliente->consultor_id === $user->id;
    }
}
