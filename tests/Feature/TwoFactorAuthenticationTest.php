<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_are_challenged_with_a_code_on_login(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => 'password-secreta',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-secreta',
        ])->assertRedirect(route('2fa.show'));

        Notification::assertSentTo($user, TwoFactorCode::class);

        $this->get('/admin/businesses')->assertRedirect(route('login'));
    }

    public function test_a_wrong_code_does_not_grant_access(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'password' => 'password-secreta',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password-secreta']);

        $this->post('/2fa', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->get('/admin/businesses')->assertRedirect(route('login'));
    }

    public function test_the_correct_code_grants_access(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'password' => 'password-secreta',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password-secreta']);

        $codigo = null;
        Notification::assertSentTo($user, TwoFactorCode::class, function ($notification) use (&$codigo) {
            $codigo = $notification->codigo;

            return true;
        });

        $this->post('/2fa', ['code' => $codigo])->assertRedirect(route('dashboard', absolute: false));
        $this->get('/admin/businesses')->assertOk();
    }

    public function test_regular_users_login_without_two_factor(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => UserRole::Professional,
            'password' => 'password-secreta',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-secreta',
        ])->assertRedirect(route('dashboard', absolute: false));

        Notification::assertNothingSent();
    }

    public function test_users_can_enable_two_factor(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($user)
            ->patchJson('/profile/2fa', ['two_factor_enabled' => true])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'two_factor_enabled' => true]);
    }

    public function test_admin_panel_requires_two_factor_verification(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->withSession(['two_factor_verified' => false])
            ->postJson("/admin/businesses/{$business->id}/activate")
            ->assertStatus(403);
    }
}
