<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use Database\Seeders\ViglamBusinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViglamBusinessSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_viglam_as_the_first_business(): void
    {
        $this->seed(ViglamBusinessSeeder::class);

        $this->assertDatabaseHas('businesses', [
            'name' => 'Viglam',
            'slug' => 'viglam',
            'active' => true,
        ]);
    }

    public function test_seeder_links_unassigned_records_to_viglam_without_altering_their_data(): void
    {
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
        ]);

        $this->seed(ViglamBusinessSeeder::class);

        $viglam = Business::where('slug', 'viglam')->firstOrFail();

        $this->assertTrue($professional->fresh()->business->is($viglam));
        $this->assertTrue($service->fresh()->business->is($viglam));
        $this->assertTrue($appointment->fresh()->business->is($viglam));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'customer_name' => 'Cliente',
            'starts_at' => '2026-11-16 08:00:00',
            'status' => 'confirmed',
        ]);

        $this->assertEquals($professional->updated_at, $professional->fresh()->updated_at);
        $this->assertEquals($appointment->updated_at, $appointment->fresh()->updated_at);
    }

    public function test_seeder_does_not_reassign_records_of_another_business(): void
    {
        $otherBusiness = Business::factory()->create();

        $professional = Professional::forceCreate([
            'name' => 'Stefania',
            'business_id' => $otherBusiness->id,
        ]);

        $this->seed(ViglamBusinessSeeder::class);

        $this->assertTrue($professional->fresh()->business->is($otherBusiness));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ViglamBusinessSeeder::class);
        $this->seed(ViglamBusinessSeeder::class);

        $this->assertDatabaseCount('businesses', 1);
    }
}
