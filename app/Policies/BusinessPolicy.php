<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /**
     * Determine whether the user can view the business.
     */
    public function view(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business);
    }

    /**
     * Determine whether the user can manage the business.
     *
     * El superadministrador administra la plataforma, no los negocios
     * de sus clientes: las operaciones de negocio exigen membresía.
     */
    public function manage(User $user, Business $business): bool
    {
        return $user->isAdmin() && $user->belongsToBusiness($business);
    }
}
