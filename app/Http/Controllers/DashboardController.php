<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Professional;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Panel principal con indicadores y accesos rápidos, con
     * alcance limitado al negocio del usuario.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $negocio = $user->business;

        $datos = [
            'negocio' => $negocio,
            'puedeGestionar' => $negocio !== null && $user->can('manage', $negocio),
        ];

        if ($user->isSuperAdmin()) {
            $datos += [
                'negociosTotal' => Business::count(),
                'negociosActivos' => Business::where('active', true)->count(),
                'membresiasPorVencer' => Membership::where('status', 'active')
                    ->whereBetween('ends_on', [now()->toDateString(), now()->addDays(5)->toDateString()])
                    ->count(),
                'citasHoy' => Appointment::whereDate('starts_at', now('America/Bogota')->toDateString())->count(),
            ];
        } elseif ($negocio !== null) {
            $datos += [
                'citasHoy' => $negocio->appointments()
                    ->whereDate('starts_at', now('America/Bogota')->toDateString())
                    ->whereIn('status', ['pending', 'confirmed'])->count(),
                'pendientes' => $negocio->appointments()->where('status', 'pending')->count(),
                'profesionalesActivos' => $negocio->professionals()->where('active', true)->count(),
                'serviciosActivos' => $negocio->services()->where('active', true)->count(),
                'clientes' => $negocio->customers()->count(),
            ];
        }

        $profesional = Professional::where('user_id', $user->id)->first();

        $datos['misCitasHoy'] = $profesional === null ? collect() : Appointment::where('professional_id', $profesional->id)
            ->whereDate('starts_at', now('America/Bogota')->toDateString())
            ->orderBy('starts_at')
            ->get();

        return view('dashboard', $datos);
    }
}
