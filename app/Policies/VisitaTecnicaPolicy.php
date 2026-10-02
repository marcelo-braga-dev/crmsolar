<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitaTecnica;

class VisitaTecnicaPolicy
{
    public function view(User $user, VisitaTecnica $visita): bool
    {
        return $visita->consultor_id === $user->id;
    }

    public function update(User $user, VisitaTecnica $visita): bool
    {
        return $visita->consultor_id === $user->id;
    }

    public function delete(User $user, VisitaTecnica $visita): bool
    {
        return $visita->consultor_id === $user->id;
    }
}
