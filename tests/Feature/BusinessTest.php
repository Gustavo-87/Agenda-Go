<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_can_be_persisted_with_its_basic_data(): void
    {
        $business = Business::factory()->create([
            'name' => 'Viglam',
            'slug' => 'viglam',
        ]);

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'name' => 'Viglam',
            'slug' => 'viglam',
        ]);
    }

    public function test_a_new_business_is_inactive_until_manually_activated(): void
    {
        $business = Business::create([
            'name' => 'Viglam',
            'slug' => 'viglam',
        ]);

        $this->assertFalse($business->fresh()->active);
    }

    public function test_the_business_slug_is_unique(): void
    {
        Business::factory()->create(['slug' => 'viglam']);

        $this->expectException(QueryException::class);

        Business::factory()->create(['slug' => 'viglam']);
    }

    public function test_a_business_with_existing_records_cannot_be_deleted(): void
    {
        $business = Business::factory()->create();

        Professional::forceCreate([
            'name' => 'Stefania',
            'business_id' => $business->id,
        ]);

        $this->expectException(QueryException::class);

        $business->delete();
    }

    public function test_professionals_are_associated_with_their_business(): void
    {
        $business = Business::factory()->create();

        $professional = Professional::forceCreate([
            'name' => 'Stefania',
            'business_id' => $business->id,
        ]);

        $this->assertTrue($business->professionals->contains($professional));
        $this->assertTrue($professional->business->is($business));
    }

    public function test_services_are_associated_with_their_business(): void
    {
        $business = Business::factory()->create();

        $professional = Professional::forceCreate(['name' => 'Stefania']);

        $service = Service::forceCreate([
            'professional_id' => $professional->id,
            'name' => 'Manos y pies',
            'duration_minutes' => 180,
            'price' => 50000,
            'business_id' => $business->id,
        ]);

        $this->assertTrue($business->services->contains($service));
        $this->assertTrue($service->business->is($business));
    }

    public function test_appointments_are_associated_with_their_business(): void
    {
        $business = Business::factory()->create();

        $professional = Professional::forceCreate(['name' => 'Stefania']);

        $service = Service::forceCreate([
            'professional_id' => $professional->id,
            'name' => 'Manos y pies',
            'duration_minutes' => 180,
            'price' => 50000,
        ]);

        $appointment = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '3001234567',
            'starts_at' => '2026-11-16 08:00:00',
            'ends_at' => '2026-11-16 11:00:00',
            'business_id' => $business->id,
        ]);

        $this->assertTrue($business->appointments->contains($appointment));
        $this->assertTrue($appointment->business->is($business));
    }
}
