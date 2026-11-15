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
        return $user->isSuperAdmin() || $user->belongsToBusiness($business);
    }

    /**
     * Determine whether the user can manage the business.
     */
    public function manage(User $user, Business $business): bool
    {
        return $user->isSuperAdmin()
            || ($user->isAdmin() && $user->belongsToBusiness($business));
    }
}
