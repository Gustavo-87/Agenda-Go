<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Determine whether the user can manage the service.
     */
    public function manage(User $user, Service $service): bool
    {
        return $service->business !== null
            ? $user->can('manage', $service->business)
            : $user->isSuperAdmin();
    }
}
