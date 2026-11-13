<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ])['date'] ?? now('America/Bogota')->toDateString();

        $appointments = DB::table('appointments')
            ->join('professionals', 'appointments.professional_id', '=', 'professionals.id')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('professionals.user_id', $request->user()->id)
            ->whereDate('appointments.starts_at', $date)
            ->select(
                'appointments.*',
                'professionals.name as professional_name',
                'services.name as service_name'
            )
            ->orderBy('appointments.starts_at')
            ->get();

        return view('agenda.index', compact('appointments', 'date'));
    }
}
