<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\MailDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_transport_failures_are_registered_and_not_reported_as_sent(): void
    {
        $business = Business::factory()->create();
        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);

        Mail::shouldReceive('send')->andThrow(new \RuntimeException('SMTP caído'));

        $respuesta = $this->actingAs($super)
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'ana@viglam.test', 'role' => 'admin'])
            ->assertStatus(202);

        $this->assertStringContainsString('falló', $respuesta->json('message'));
        $this->assertStringContainsString('registrada', $respuesta->json('message'));

        $this->assertDatabaseHas('invitations', ['email' => 'ana@viglam.test']);
        $this->assertDatabaseHas('mail_deliveries', ['recipient' => 'ana@viglam.test', 'status' => 'failed']);
    }

    public function test_successful_transport_is_reported_as_provider_accepted(): void
    {
        $business = Business::factory()->create();
        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $respuesta = $this->actingAs($super)
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'ana@viglam.test', 'role' => 'admin'])
            ->assertStatus(201);

        $this->assertStringContainsString('proveedor de correo', $respuesta->json('message'));

        $delivery = MailDelivery::where('recipient', 'ana@viglam.test')->firstOrFail();
        $this->assertSame('accepted', $delivery->status);
        $this->assertNotNull($delivery->sent_at);
    }

    public function test_otp_transport_failures_show_a_warning_without_blocking_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Professional,
            'two_factor_enabled' => true,
            'password' => 'password-secreta',
        ]);

        Mail::shouldReceive('send')->andThrow(new \RuntimeException('SMTP caído'));

        $respuesta = $this->post('/login', ['email' => $user->email, 'password' => 'password-secreta'])
            ->assertRedirect(route('2fa.show'));

        $respuesta->assertSessionHas('advertencia');
        $this->assertStringContainsString('two-factor:code', session('advertencia'));
        $this->assertDatabaseHas('mail_deliveries', ['recipient' => $user->email, 'status' => 'failed']);
    }

    public function test_password_recovery_reports_transport_failures_honestly(): void
    {
        $user = User::factory()->create(['password' => 'password-vieja']);

        Mail::shouldReceive('send')->andThrow(new \RuntimeException('SMTP caído'));

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('status');

        $this->assertDatabaseHas('mail_deliveries', ['recipient' => $user->email, 'status' => 'failed']);
    }

    public function test_the_real_send_probe_reports_provider_result_without_secrets(): void
    {
        $this->artisan('mail:test', ['email' => 'prueba@viglam.test'])
            ->expectsOutputToContain('aceptado por el proveedor de correo')
            ->assertSuccessful();

        Mail::shouldReceive('send')->andThrow(new \RuntimeException('SMTP caído'));

        $this->artisan('mail:test', ['email' => 'prueba@viglam.test'])
            ->expectsOutputToContain('rechazó o no aceptó')
            ->assertFailed();
    }

    public function test_failed_deliveries_can_be_reviewed(): void
    {
        MailDelivery::create([
            'notification' => 'App\Notifications\InvitationNotification',
            'recipient' => 'ana@viglam.test',
            'status' => 'failed',
            'error' => 'SMTP caído',
        ]);

        $this->artisan('mail:failures')
            ->expectsOutputToContain('SMTP caído')
            ->assertSuccessful();
    }
}
