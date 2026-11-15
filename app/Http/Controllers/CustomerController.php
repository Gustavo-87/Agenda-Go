<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request, Business $business)
    {
        abort_unless($request->user()->can('view', $business), 403);

        return response()->json([
            'data' => $business->customers()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Customer $customer)
    {
        abort_unless($customer->business !== null && $request->user()->can('view', $customer->business), 403);

        return response()->json([
            'data' => $customer,
            'historial' => $customer->appointments()->orderByDesc('starts_at')->get(),
        ]);
    }

    /**
     * Aviso y autorización de tratamiento de datos (RN de privacidad).
     */
    public function consent(Request $request, Customer $customer)
    {
        abort_unless($customer->business !== null && $request->user()->can('manage', $customer->business), 403);

        $data = $request->validate(['data_consent' => ['required', 'boolean']]);

        $customer->update([
            'data_consent' => (bool) $data['data_consent'],
            'data_consent_at' => now(),
        ]);

        return response()->json(['message' => 'Consentimiento registrado']);
    }
}
