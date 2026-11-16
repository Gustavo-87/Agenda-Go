<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Invitation;
use App\Models\Professional;
use App\Models\ScheduleException;
use App\Models\User;
use App\Models\WorkSchedule;
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

        return $this->respuesta($request, 'Profesional creado', ['professional_id' => $professional->id], 201);
    }

    /**
     * Pantalla de gestión del negocio: profesionales, servicios,
     * horarios, excepciones y festivos.
     */
    public function pantalla(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        $business = $this->contextoNegocio($request);

        if ($business !== null) {
            abort_unless($user->can('manage', $business), 403);
        }

        $professionalIds = $business?->professionals()->pluck('id') ?? collect();

        $q = trim((string) $request->query('q', ''));
        $estado = (string) $request->query('estado', 'todos');
        $profesionalFiltro = $request->query('professional_id');

        $profesionales = $business?->professionals()->orderBy('name')->get() ?? collect();
        $servicios = $business?->services()->orderBy('name')->get() ?? collect();

        if ($q !== '') {
            $profesionales = $profesionales->filter(
                fn ($item) => str_contains(mb_strtolower($item->name), mb_strtolower($q))
                    || str_contains((string) $item->phone, $q)
            )->values();

            $servicios = $servicios->filter(
                fn ($item) => str_contains(mb_strtolower($item->name), mb_strtolower($q))
            )->values();
        }

        if (in_array($estado, ['active', 'inactive'], true)) {
            $servicios = $servicios->filter(
                fn ($item) => (bool) $item->active === ($estado === 'active')
            )->values();
        }

        $horarios = WorkSchedule::whereIn('professional_id', $professionalIds)
            ->when($profesionalFiltro, fn ($query) => $query->where('professional_id', (int) $profesionalFiltro))
            ->get()->groupBy('professional_id');

        $excepciones = ScheduleException::whereIn('professional_id', $professionalIds)
            ->when($profesionalFiltro, fn ($query) => $query->where('professional_id', (int) $profesionalFiltro))
            ->orderBy('starts_on')->get();

        $festivos = $business?->holidays()->orderBy('date')->get() ?? collect();

        if ($q !== '') {
            $festivos = $festivos->filter(
                fn ($item) => str_contains(mb_strtolower((string) $item->name), mb_strtolower($q))
                    || str_contains((string) $item->date, $q)
            )->values();
        }

        return view('gestion.index', [
            'negocio' => $business,
            'q' => $q,
            'estado' => $estado,
            'profesionalFiltro' => $profesionalFiltro,
            'profesionales' => $profesionales,
            'usuarios' => $business?->users()->orderBy('name')->get() ?? collect(),
            'invitaciones' => $business === null ? collect() : Invitation::where('business_id', $business->id)
                ->where('role', 'professional')->whereNull('accepted_at')->orderByDesc('id')->get(),
            'servicios' => $servicios,
            'horarios' => $horarios,
            'excepciones' => $excepciones,
            'festivos' => $festivos,
            'negocios' => $user->isSuperAdmin() ? Business::orderBy('name')->get() : collect(),
        ]);
    }

    public function update(Request $request, Professional $professional)
    {
        $this->assertPuedeGestionar($request->user(), $professional);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $professional->update($data);

        return $this->respuesta($request, 'Profesional actualizado');
    }

    public function toggle(Request $request, Professional $professional)
    {
        $this->assertPuedeGestionar($request->user(), $professional);

        if (! $professional->active && $professional->business !== null) {
            $this->assertLimiteProfesionalesActivos($professional->business, $professional->id);
        }

        $professional->update(['active' => ! $professional->active]);

        $mensaje = $professional->active ? 'Profesional activado' : 'Profesional desactivado';

        return $this->respuesta($request, $mensaje, ['active' => $professional->active]);
    }

    /**
     * Vincula el usuario que accederá a la agenda de la profesional.
     */
    public function assignUser(Request $request, Professional $professional)
    {
        $this->assertPuedeGestionar($request->user(), $professional);

        $data = $request->validate(['user_id' => ['nullable', 'integer', 'exists:users,id']]);

        $userId = $data['user_id'] ?: null;

        if ($userId !== null) {
            $user = User::find($userId);

            if ($user === null || $user->business_id !== $professional->business_id) {
                throw ValidationException::withMessages([
                    'user_id' => 'El usuario no pertenece a este negocio.',
                ]);
            }
        }

        $professional->update(['user_id' => $userId]);

        return $this->respuesta($request, 'Usuario vinculado a la profesional');
    }

    /**
     * Datos de contacto de la profesional: los edita ella misma o
     * la administración de su negocio.
     */
    public function updateContact(Request $request, Professional $professional)
    {
        $user = $request->user();

        abort_unless(
            $professional->user_id === $user->id || $user->can('manage', $professional),
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $professional->update($data);

        return $this->respuesta($request, 'Datos de contacto actualizados');
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
        abort_unless($user->can('manage', $professional), 403);
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
