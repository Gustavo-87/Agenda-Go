<?php

namespace App\Policies;

use App\Models\Professional;
use App\Models\User;

class ProfessionalPolicy
{
    /**
     * Determine whether the user can manage the professional.
     */
    public function manage(User $user, Professional $professional): bool
    {
        return $professional->business !== null
            ? $user->can('manage', $professional->business)
            : $user->isSuperAdmin();
    }
}
