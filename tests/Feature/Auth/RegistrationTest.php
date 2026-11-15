<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@viglam.test',
            'password' => 'password-secreta',
            'password_confirmation' => 'password-secreta',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_cannot_create_admins_or_businesses(): void
    {
        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@viglam.test',
            'password' => 'password-secreta',
            'password_confirmation' => 'password-secreta',
            'role' => 'super_admin',
            'business_id' => 1,
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }
}
