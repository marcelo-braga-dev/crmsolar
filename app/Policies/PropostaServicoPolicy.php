<?php

namespace App\Policies;

use App\Models\PropostaServico;
use App\Models\User;

class PropostaServicoPolicy
{
    public function view(User $user, PropostaServico $proposta): bool
    {
        return $proposta->consultor_id === $user->id;
    }

    public function update(User $user, PropostaServico $proposta): bool
    {
        return $proposta->consultor_id === $user->id;
    }

    public function delete(User $user, PropostaServico $proposta): bool
    {
        return $proposta->consultor_id === $user->id;
    }
}
