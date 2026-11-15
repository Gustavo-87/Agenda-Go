<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
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

        return response()->json(['message' => 'Política de reservas actualizada']);
    }
}
