<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Membership;
use App\Models\User;
use Database\Seeders\DemoBusinessSeeder;
use Database\Seeders\ViglamBusinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaaSSetupTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_changing_a_user_role_preserves_their_business(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->patch('/admin/users/'.$user->id, ['role' => 'admin'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
            'business_id' => $business->id,
        ]);
    }

    public function test_the_role_command_promotes_users_locally(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);

        $this->artisan('user:role', ['email' => $user->email, 'role' => 'super_admin'])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'super_admin']);

        $this->artisan('user:role', ['email' => $user->email, 'role' => 'invalido'])
            ->assertFailed();

        $this->artisan('user:role', ['email' => 'desconocido@viglam.test', 'role' => 'admin'])
            ->assertFailed();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'super_admin']);
    }

    public function test_demo_data_creates_two_independent_businesses_without_touching_viglam(): void
    {
        $this->seed(ViglamBusinessSeeder::class);
        $viglam = Business::where('slug', 'viglam')->firstOrFail();
        $viglamSnapshot = [
            'name' => $viglam->name,
            'profesionales' => $viglam->professionals()->count(),
            'servicios' => $viglam->services()->count(),
            'reservas' => $viglam->appointments()->count(),
        ];

        $this->seed(DemoBusinessSeeder::class);

        $bella = Business::where('slug', 'bella-estetica')->firstOrFail();
        $norte = Business::where('slug', 'barberia-norte')->firstOrFail();

        $this->assertTrue($bella->active);
        $this->assertTrue($norte->active);
        $this->assertNotSame($bella->id, $norte->id);

        foreach ([$bella, $norte] as $business) {
            $this->assertSame(1, $business->users()->where('role', 'admin')->count());
            $this->assertSame(1, $business->professionals()->count());
            $this->assertSame(1, $business->services()->count());
            $this->assertSame(1, $business->customers()->count());
            $this->assertSame(2, $business->appointments()->count());
        }

        $this->assertSame($viglamSnapshot['name'], $viglam->fresh()->name);
        $this->assertSame($viglamSnapshot['profesionales'], $viglam->fresh()->professionals()->count());
        $this->assertSame($viglamSnapshot['servicios'], $viglam->fresh()->services()->count());
        $this->assertSame($viglamSnapshot['reservas'], $viglam->fresh()->appointments()->count());
    }

    public function test_super_admin_panel_exposes_the_expected_crud_forms(): void
    {
        $business = Business::factory()->create();
        User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
        Membership::create([
            'business_id' => $business->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'amount' => 150000,
        ]);
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->get('/admin/businesses/'.$business->id)
            ->assertOk()
            ->assertSee('Invitar usuarios')
            ->assertSee('Datos del negocio')
            ->assertSee('Membresías del negocio')
            ->assertSee('Cambiar rol')
            ->assertSee('Desvincular');

        $this->actingAs($super)
            ->get('/admin/memberships')
            ->assertOk()
            ->assertSee('Registro de membresía')
            ->assertSee('Renovar');
    }

    public function test_admin_forms_show_labels_guidance_and_required_indicators(): void
    {
        $business = Business::factory()->create();
        Membership::create([
            'business_id' => $business->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'amount' => 150000,
        ]);
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->get('/admin/memberships')
            ->assertOk()
            ->assertSee('Fecha de inicio')
            ->assertSee('Fecha de vencimiento')
            ->assertSee('Valor mensual (COP)')
            ->assertSee('Fecha de pago')
            ->assertSee('son obligatorios');

        $this->actingAs($super)
            ->get('/admin/businesses/'.$business->id)
            ->assertOk()
            ->assertSee('Correo electrónico')
            ->assertSee('Rol')
            ->assertSee('Valor mensual (COP)')
            ->assertSee('Primer día del período mensual');

        $this->actingAs($super)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Nombre del negocio')
            ->assertSee('Identificador (slug)');
    }
}
