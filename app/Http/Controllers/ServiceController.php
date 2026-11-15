<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        return response()->json([
            'data' => $business->services()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'professional_id' => ['required', 'integer', 'exists:professionals,id'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $this->assertProfesionalDelNegocio($business, (int) $data['professional_id']);

        $service = Service::create([
            'name' => $data['name'],
            'duration_minutes' => $data['duration_minutes'],
            'price' => $data['price'],
            'active' => $data['active'] ?? true,
            'professional_id' => $data['professional_id'],
            'business_id' => $business->id,
        ]);

        return $this->respuesta($request, 'Servicio creado', ['service_id' => $service->id], 201);
    }

    public function update(Request $request, Service $service)
    {
        abort_unless($request->user()->can('manage', $service), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:720'],
            'price' => ['sometimes', 'integer', 'min:0', 'max:100000000'],
            'professional_id' => ['sometimes', 'integer', 'exists:professionals,id'],
        ]);

        if (isset($data['professional_id']) && $service->business !== null) {
            $this->assertProfesionalDelNegocio($service->business, (int) $data['professional_id']);
        }

        $service->update($data);

        return $this->respuesta($request, 'Servicio actualizado');
    }

    public function toggle(Request $request, Service $service)
    {
        abort_unless($request->user()->can('manage', $service), 403);

        $service->update(['active' => ! $service->active]);

        $mensaje = $service->active ? 'Servicio activado' : 'Servicio desactivado';

        return $this->respuesta($request, $mensaje, ['active' => $service->active]);
    }

    /**
     * A service can only be assigned to a professional of the same business.
     */
    private function assertProfesionalDelNegocio(Business $business, int $professionalId): void
    {
        $pertenece = Professional::whereKey($professionalId)
            ->where('business_id', $business->id)
            ->exists();

        if (! $pertenece) {
            throw ValidationException::withMessages([
                'professional_id' => 'El profesional no pertenece a este negocio.',
            ]);
        }
    }
}
