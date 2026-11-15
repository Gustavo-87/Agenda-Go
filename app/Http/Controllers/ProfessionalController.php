<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfessionalController extends Controller
{
    public function index(Request $request)
    {
        $business = $this->negocioGestionable($request);

        return response()->json([
            'data' => $business->professionals()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->negocioGestionable($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $activo = $data['active'] ?? true;

        if ($activo) {
            $this->assertLimiteProfesionalesActivos($business);
        }

        $professional = Professional::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'active' => $activo,
            'business_id' => $business->id,
        ]);

        return response()->json([
            'message' => 'Profesional creado',
            'professional_id' => $professional->id,
        ], 201);
    }

    public function update(Request $request, Professional $professional)
    {
        $this->assertPuedeGestionar($request->user(), $professional);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $professional->update($data);

        return response()->json(['message' => 'Profesional actualizado']);
    }

    public function toggle(Request $request, Professional $professional)
    {
        $this->assertPuedeGestionar($request->user(), $professional);

        if (! $professional->active && $professional->business !== null) {
            $this->assertLimiteProfesionalesActivos($professional->business, $professional->id);
        }

        $professional->update(['active' => ! $professional->active]);

        return response()->json([
            'message' => $professional->active ? 'Profesional activado' : 'Profesional desactivado',
            'active' => $professional->active,
        ]);
    }

    /**
     * Business the authenticated user manages: their own, or any
     * business when the user is a super admin and selects it.
     */
    private function negocioGestionable(Request $request): Business
    {
        $user = $request->user();

        $businessId = $user->isSuperAdmin()
            ? (int) $request->input('business_id', $user->business_id)
            : (int) $user->business_id;

        $business = $businessId > 0 ? Business::find($businessId) : null;

        abort_unless($business !== null && $user->can('manage', $business), 403);

        return $business;
    }

    private function assertPuedeGestionar(User $user, Professional $professional): void
    {
        $autorizado = $professional->business !== null
            ? $user->can('manage', $professional->business)
            : $user->isSuperAdmin();

        abort_unless($autorizado, 403);
    }

    /**
     * RN-002: a business can have at most 5 active professionals.
     */
    private function assertLimiteProfesionalesActivos(Business $business, ?int $exceptId = null): void
    {
        $activos = Professional::where('business_id', $business->id)
            ->where('active', true)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->count();

        if ($activos >= 5) {
            throw ValidationException::withMessages([
                'active' => 'El negocio no puede tener más de 5 profesionales activos.',
            ]);
        }
    }
}
