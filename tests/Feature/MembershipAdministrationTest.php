<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Jobs\EnviarNotificacionWhatsApp;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MembershipAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    private function negocioHabilitado(): array
    {
        config(['services.reservas.business_slug' => 'canal-membresia']);
        $business = Business::factory()->create(['slug' => 'canal-membresia']);
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        return [$business, $professional, $service];
    }

    private function reservar(Professional $professional, Service $service, string $startsAt)
    {
        return $this->postJson('/api/appointments', [
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '3000000000',
            'starts_at' => $startsAt,
        ]);
    }

    public function test_super_admin_can_register_and_activate_a_business(): void
    {
        $super = $this->superAdmin();

        $respuesta = $this->actingAs($super)
            ->postJson('/admin/businesses', ['name' => 'Viglam', 'slug' => 'viglam'])
            ->assertStatus(201);

        $businessId = $respuesta->json('business_id');

        $this->assertDatabaseHas('businesses', ['id' => $businessId, 'active' => false]);

        $this->actingAs($super)
            ->postJson("/admin/businesses/{$businessId}/activate")
            ->assertStatus(200);

        $this->assertDatabaseHas('businesses', ['id' => $businessId, 'active' => true]);
    }

    public function test_only_super_admins_can_use_the_admin_panel(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)->getJson('/admin/businesses')->assertStatus(403);
        $this->actingAs($admin)->postJson('/admin/businesses', ['name' => 'X', 'slug' => 'x'])->assertStatus(403);
        $this->actingAs($admin)->postJson("/admin/businesses/{$business->id}/activate")->assertStatus(403);
    }

    public function test_a_membership_is_activated_with_a_verified_payment(): void
    {
        $super = $this->superAdmin();
        $business = Business::factory()->create(['active' => false]);

        $respuesta = $this->actingAs($super)
            ->postJson("/admin/businesses/{$business->id}/memberships", [
                'starts_on' => '2026-11-01',
                'ends_on' => '2026-11-30',
                'amount' => 150000,
                'paid_on' => '2026-11-01',
                'method' => 'transferencia',
                'reference' => 'REF-001',
            ])
            ->assertStatus(201);

        $paymentId = $respuesta->json('payment_id');

        $this->assertDatabaseHas('membership_payments', [
            'id' => $paymentId,
            'verified' => false,
        ]);

        $this->actingAs($super)
            ->postJson("/admin/membership-payments/{$paymentId}/verify")
            ->assertStatus(200);

        $this->assertDatabaseHas('membership_payments', ['id' => $paymentId, 'verified' => true]);
        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'active' => true]);
    }

    public function test_renewal_creates_the_next_period(): void
    {
        $super = $this->superAdmin();
        $business = Business::factory()->create();
        $membership = Membership::create([
            'business_id' => $business->id,
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-30',
            'amount' => 150000,
        ]);

        $this->actingAs($super)
            ->postJson("/admin/memberships/{$membership->id}/renew", [
                'amount' => 150000,
                'paid_on' => '2026-11-28',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('memberships', [
            'business_id' => $business->id,
            'starts_on' => '2026-12-01',
            'ends_on' => '2026-12-31',
        ]);
    }

    public function test_new_reservations_are_blocked_after_the_grace_period(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->negocioHabilitado();

        Membership::create([
            'business_id' => $business->id,
            'starts_on' => now()->subMonths(2)->toDateString(),
            'ends_on' => now()->subDays(6)->toDateString(),
            'amount' => 150000,
        ]);

        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $this->reservar($professional, $service, "{$fecha} 09:00:00")
            ->assertStatus(422);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_suspension_blocks_new_reservations_and_preserves_history(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->negocioHabilitado();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $cita = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '300',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson("/admin/businesses/{$business->id}/suspend")
            ->assertStatus(200);

        $this->reservar($professional, $service, "{$fecha} 11:00:00")
            ->assertStatus(422);

        $this->assertDatabaseHas('appointments', ['id' => $cita->id]);
    }

    public function test_expiring_memberships_trigger_notifications(): void
    {
        Queue::fake();
        $business = Business::factory()->create(['phone' => '3000000000']);
        Membership::create([
            'business_id' => $business->id,
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addDays(5)->toDateString(),
            'amount' => 150000,
        ]);

        $renovado = Business::factory()->create(['phone' => '3010000000']);
        Membership::create([
            'business_id' => $renovado->id,
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addDays(2)->toDateString(),
            'amount' => 150000,
        ]);
        Membership::create([
            'business_id' => $renovado->id,
            'starts_on' => now()->addDays(3)->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'amount' => 150000,
        ]);

        $this->artisan('memberships:notify-expiring')->assertSuccessful();

        Queue::assertPushed(EnviarNotificacionWhatsApp::class, 1);
    }

    public function test_cancellation_keeps_service_until_the_end_of_the_paid_period(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->negocioHabilitado();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $membership = Membership::create([
            'business_id' => $business->id,
            'starts_on' => now()->subDays(5)->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'amount' => 150000,
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson("/admin/memberships/{$membership->id}/cancel")
            ->assertStatus(200);

        $this->assertDatabaseHas('memberships', ['id' => $membership->id, 'status' => 'cancelled']);

        // El servicio se conserva hasta finalizar el período pagado.
        $this->reservar($professional, $service, "{$fecha} 09:00:00")
            ->assertStatus(201);

        // Finalizado el período con gracia, no hay nuevas reservas.
        $this->travel(20)->days();

        $this->reservar($professional, $service, now('America/Bogota')->addDays(3)->format('Y-m-d 09:00:00'))
            ->assertStatus(422);
    }
}
