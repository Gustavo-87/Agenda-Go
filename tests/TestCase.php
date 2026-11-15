<?php

namespace Tests;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Los administradores requieren verificación de dos factores
     * (RN-043). En pruebas se otorga automáticamente, salvo cuando
     * el flujo de 2FA es el objeto de la prueba.
     */
    public function actingAs($user, $driver = null)
    {
        if (in_array($user->role, [UserRole::Admin, UserRole::SuperAdmin], true)) {
            $this->withSession(['two_factor_verified' => true]);
        }

        return parent::actingAs($user, $driver);
    }
}
