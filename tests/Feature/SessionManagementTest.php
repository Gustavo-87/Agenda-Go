<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_is_visible_and_invalidates_the_session_for_every_role(): void
    {
        $roles = [UserRole::SuperAdmin, UserRole::Admin, UserRole::Professional];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Cerrar sesión');

            $this->post('/logout')->assertRedirect('/');

            $this->assertGuest();

            $this->get('/dashboard')->assertRedirect(route('login'));
            $this->get('/agenda')->assertRedirect(route('login'));
            $this->get('/admin')->assertRedirect(route('login'));
        }
    }

    public function test_the_password_reset_flow_works_by_email(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => UserRole::Professional,
            'password' => 'password-vieja',
        ]);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', __('passwords.sent'));

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get('/reset-password/'.$token)->assertOk()->assertSee('Restablecer contraseña');

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-nueva',
            'password_confirmation' => 'password-nueva',
        ])->assertRedirect(route('login'));

        $this->post('/login', ['email' => $user->email, 'password' => 'password-nueva'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_expired_reset_tokens_are_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'password-vieja']);

        $token = Password::broker()->createToken($user);

        $this->travel(61)->minutes();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-nueva',
            'password_confirmation' => 'password-nueva',
        ])->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $user->email, 'password' => 'password-vieja'])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_unknown_emails_get_a_friendly_spanish_message(): void
    {
        $this->post('/forgot-password', ['email' => 'desconocido@viglam.test'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'No podemos encontrar ningún usuario',
            session('errors')->first('email')
        );
    }

    public function test_the_password_can_be_changed_from_the_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Professional,
            'password' => 'password-vieja',
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password-vieja',
                'password' => 'password-nueva',
                'password_confirmation' => 'password-nueva',
            ])
            ->assertRedirect('/profile');

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password-nueva'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_logout_clears_two_factor_session_state(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => UserRole::Professional,
            'two_factor_enabled' => true,
            'password' => 'password-secreta',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password-secreta'])
            ->assertRedirect(route('2fa.show'));

        $this->assertGuest();

        $codigo = null;
        Notification::assertSentTo($user, TwoFactorCode::class, function ($notification) use (&$codigo) {
            $codigo = $notification->codigo;

            return true;
        });

        $this->post('/2fa', ['code' => $codigo])->assertRedirect(route('dashboard', absolute: false));

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
