<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos sintéticos para probar dos negocios independientes.
 * No toca los datos de Viglam ni ningún negocio existente.
 */
class DemoBusinessSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $negocios = [
            ['Bella Estética', 'bella-estetica', 'estética'],
            ['Barbería Norte', 'barberia-norte', 'barbería'],
        ];

        foreach ($negocios as [$nombre, $slug, $sector]) {
            $business = Business::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $nombre,
                    'sector' => $sector,
                    'email' => "contacto@{$slug}.test",
                    'phone' => '3000000000',
                    'active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => "admin@{$slug}.test"],
                [
                    'name' => 'Admin '.$nombre,
                    'password' => 'password',
                    'business_id' => $business->id,
                    'role' => UserRole::Admin,
                ]
            );

            $profesionalUser = User::updateOrCreate(
                ['email' => "pro@{$slug}.test"],
                [
                    'name' => 'Profesional '.$nombre,
                    'password' => 'password',
                    'business_id' => $business->id,
                    'role' => UserRole::Professional,
                ]
            );

            $professional = Professional::updateOrCreate(
                ['name' => 'Profesional '.$nombre, 'business_id' => $business->id],
                ['phone' => '3100000000', 'active' => true, 'user_id' => $profesionalUser->id]
            );

            $service = Service::updateOrCreate(
                ['name' => 'Servicio principal', 'professional_id' => $professional->id],
                ['business_id' => $business->id, 'duration_minutes' => 60, 'price' => 50000, 'active' => true]
            );

            $customer = Customer::updateOrCreate(
                ['business_id' => $business->id, 'phone' => '3200000000'],
                ['name' => 'Cliente '.$nombre]
            );

            foreach ([0, 1] as $dia) {
                $inicio = now('America/Bogota')->addDays(3 + $dia)->setTime(9 + ($dia * 2), 0);
                Appointment::firstOrCreate(
                    [
                        'professional_id' => $professional->id,
                        'starts_at' => $inicio->format('Y-m-d H:i:s'),
                    ],
                    [
                        'service_id' => $service->id,
                        'business_id' => $business->id,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_phone' => $customer->phone,
                        'ends_at' => $inicio->copy()->addHour()->format('Y-m-d H:i:s'),
                        'status' => 'confirmed',
                    ]
                );
            }
        }
    }
}
