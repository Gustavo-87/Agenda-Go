<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Respuesta dual: JSON para clientes API y redirección con
     * mensaje flash para formularios web.
     */
    protected function respuesta(Request $request, string $mensaje, array $data = [], int $status = 200)
    {
        return $request->expectsJson()
            ? response()->json(array_merge(['message' => $mensaje], $data), $status)
            : redirect()->back()->with('success', $mensaje);
    }

    /**
     * Negocio en contexto: el asignado al usuario, el vinculado a su
     * profesional o el seleccionado por el superadministrador.
     */
    protected function contextoNegocio(Request $request): ?Business
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $id = (int) $request->query('business_id', 0);

            return $id > 0 ? Business::find($id) : null;
        }

        return $user->business ?? Business::find(
            Professional::where('user_id', $user->id)->value('business_id')
        );
    }

    /**
     * Citas visibles para el usuario: todo el negocio para quien lo
     * gestiona y solo la agenda propia para los profesionales.
     *
     * @return Builder<Appointment>
     */
    protected function citasDelContexto(Request $request): Builder
    {
        $user = $request->user();
        $business = $this->contextoNegocio($request);

        $query = Appointment::query()->with(['professional', 'service']);

        if ($user->isSuperAdmin()) {
            return $query->whereNull('appointments.id');
        }

        if ($business !== null && $user->can('manage', $business)) {
            return $query->where('business_id', $business->id);
        }

        return $query->whereHas('professional', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
