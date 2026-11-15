<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Professional;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Configuración del negocio y de la cuenta del usuario.
     */
    public function pantalla(Request $request)
    {
        $user = $request->user();

        $business = $user->isSuperAdmin() ? null : $this->contextoNegocio($request);

        if ($business !== null) {
            abort_unless($user->can('view', $business), 403);
        }

        return view('settings.index', [
            'negocio' => $business,
            'puedeGestionar' => $business !== null && $user->can('manage', $business),
            'negocios' => collect(),
            'miProfesional' => Professional::where('user_id', $user->id)->first(),
        ]);
    }

    /**
     * RN-020 / RN-021 / RN-022: políticas de reserva del negocio.
     */
    public function updateBookingPolicy(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $data = $request->validate([
            'booking_min_notice_minutes' => ['sometimes', 'integer', 'min:0', 'max:10080'],
            'booking_max_advance_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'booking_auto_confirm' => ['sometimes', 'boolean'],
            'booking_pending_ttl_minutes' => ['sometimes', 'integer', 'min:5', 'max:1440'],
        ]);

        $business->update($data);

        return $this->respuesta($request, 'Política de reservas actualizada');
    }
}
