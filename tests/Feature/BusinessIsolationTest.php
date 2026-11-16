<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Services\ReservaWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BusinessIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function useAsBookingChannel(Business $business): void
    {
        config(['services.reservas.business_slug' => $business->slug]);
    }

    private function professionalWithService(Business $business, string $name): array
    {
        $professional = Professional::forceCreate([
            'name' => $name,
            'business_id' => $business->id,
        ]);

        $service = Service::forceCreate([
            'professional_id' => $professional->id,
            'business_id' => $business->id,
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
        ]);

        return [$professional, $service];
    }

    public function test_a_reservation_cannot_mix_professionals_and_services_across_businesses(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();
        $this->useAsBookingChannel($businessA);

        [$professionalA] = $this->professionalWithService($businessA, 'Stefania');
        [, $serviceB] = $this->professionalWithService($businessB, 'Laura');

        $this->postJson('/api/appointments', [
            'professional_id' => $professionalA->id,
            'service_id' => $serviceB->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '3000000000',
            'starts_at' => now('America/Bogota')->addDays(3)->format('Y-m-d').' 09:00:00',
        ])->assertStatus(422);
    }

    public function test_the_booking_channel_cannot_query_or_reserve_resources_of_another_business(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();
        $this->useAsBookingChannel($businessA);

        [$professionalB, $serviceB] = $this->professionalWithService($businessB, 'Laura');

        $this->getJson("/api/availability?professional_id={$professionalB->id}&service_id={$serviceB->id}&date=".now('America/Bogota')->addDays(3)->toDateString())
            ->assertStatus(422);

        $this->postJson('/api/appointments', [
            'professional_id' => $professionalB->id,
            'service_id' => $serviceB->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '3000000000',
            'starts_at' => now('America/Bogota')->addDays(3)->format('Y-m-d').' 09:00:00',
        ])->assertStatus(422);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_a_reservation_within_the_channel_business_is_confirmed(): void
    {
        Queue::fake();

        $business = Business::factory()->create();
        $this->useAsBookingChannel($business);

        [$professional, $service] = $this->professionalWithService($business, 'Stefania');

        $this->postJson('/api/appointments', [
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => 'Cliente Viglam',
            'customer_phone' => '3000000000',
            'starts_at' => now('America/Bogota')->addDays(3)->format('Y-m-d').' 09:00:00',
        ])->assertStatus(201);

        $this->assertDatabaseHas('appointments', [
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => 'Cliente Viglam',
        ]);
    }

    public function test_the_whatsapp_menu_excludes_professionals_from_another_business(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();
        $this->useAsBookingChannel($businessA);

        $this->professionalWithService($businessA, 'Stefania');
        $this->professionalWithService($businessB, 'Laura');

        $respuesta = app(ReservaWhatsAppService::class)->procesar('3000000000', 'hola');

        $this->assertStringContainsString('Agendar cita', $respuesta);
        $this->assertStringNotContainsString('Laura', $respuesta);

        $respuesta = app(ReservaWhatsAppService::class)->procesar('3000000000', '1');

        $this->assertStringContainsString('Stefania', $respuesta);
        $this->assertStringNotContainsString('Laura', $respuesta);
    }

    public function test_an_admin_of_another_business_cannot_manage_a_professionals_google_calendar(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        [$professionalA] = $this->professionalWithService($businessA, 'Stefania');
        $adminB = User::factory()->create([
            'business_id' => $businessB->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($adminB)
            ->get("/google/connect/{$professionalA->id}")
            ->assertStatus(403);
    }

    public function test_an_admin_can_manage_the_professionals_of_their_own_business(): void
    {
        $business = Business::factory()->create();

        [$professional] = $this->professionalWithService($business, 'Stefania');
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->get("/google/connect/{$professional->id}")
            ->assertStatus(302);
    }

    public function test_a_super_admin_can_manage_any_professionals_google_calendar(): void
    {
        $business = Business::factory()->create();

        [$professional] = $this->professionalWithService($business, 'Stefania');
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->get("/google/connect/{$professional->id}")
            ->assertStatus(302);
    }
}
