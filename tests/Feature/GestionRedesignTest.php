<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
        $stefania = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $laura = Professional::create(['name' => 'Laura', 'business_id' => $business->id]);

        return [$business, $admin, $stefania, $laura];
    }

    public function test_lists_show_summary_and_actions_without_inline_editing(): void
    {
        [$business, $admin, $stefania] = $this->escenario();
        Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $stefania->id,
            'business_id' => $business->id,
        ]);
        WorkSchedule::create([
            'professional_id' => $stefania->id,
            'weekday' => 0,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($admin)
            ->get('/gestion')
            ->assertOk()
            ->assertSee('Nuevo profesional')
            ->assertSee('Nuevo servicio')
            ->assertSee('Nueva jornada')
            ->assertSee('Nueva excepción')
            ->assertSee('Nuevo festivo')
            ->assertSee('Editar')
            ->assertSee('Desactivar')
            ->assertSee('editar-profesional-'.$stefania->id, false)
            ->assertSee('Listado de jornadas y descansos')
            ->assertSee('Jornada');
    }

    public function test_search_and_filters_narrow_the_lists(): void
    {
        [, $admin] = $this->escenario();

        $this->actingAs($admin)
            ->get('/gestion?tab=profesionales&q=Stef')
            ->assertOk()
            ->assertSee('Stefania')
            ->assertDontSee('Laura');

        [$business, $admin, $stefania] = $this->escenario();
        Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $stefania->id,
            'business_id' => $business->id,
        ]);
        Service::create([
            'name' => 'Esmaltado',
            'duration_minutes' => 30,
            'price' => 30000,
            'professional_id' => $stefania->id,
            'business_id' => $business->id,
            'active' => false,
        ]);

        $this->actingAs($this->adminOf($business))
            ->get('/gestion?tab=servicios&estado=inactive')
            ->assertOk()
            ->assertSee('Esmaltado')
            ->assertDontSee('Semipermanente');
    }

    public function test_creation_panels_keep_labels_and_hide_until_requested(): void
    {
        [, $admin] = $this->escenario();

        $this->actingAs($admin)
            ->get('/gestion')
            ->assertOk()
            ->assertSee('x-show="nuevo === \'servicios\'"', false)
            ->assertSee('Duración (minutos)')
            ->assertSee('Precio (COP)')
            ->assertSee('Desde (fecha)')
            ->assertSee('Día de la semana');
    }

    private function adminOf(Business $business): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
    }
}
