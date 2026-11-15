<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Listado de clientes del negocio.
     */
    public function pantalla(Request $request)
    {
        $user = $request->user();
        $business = $this->contextoNegocio($request);
        $q = trim((string) $request->query('q', ''));

        if ($business !== null) {
            abort_unless($user->can('view', $business), 403);
        }

        return view('customers.index', [
            'negocio' => $business,
            'q' => $q,
            'clientes' => $business?->customers()
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('name', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
                })
                ->orderBy('name')->paginate(10)->withQueryString() ?? collect(),
            'negocios' => $user->isSuperAdmin() ? Business::orderBy('name')->get() : collect(),
        ]);
    }

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

        $historial = $customer->appointments()->orderByDesc('starts_at')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $customer,
                'historial' => $historial,
            ]);
        }

        return view('customers.show', [
            'cliente' => $customer,
            'historial' => $historial,
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

        return $this->respuesta($request, 'Consentimiento registrado');
    }
}
