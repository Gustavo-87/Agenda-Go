<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Throwable;

class AppointmentController extends Controller
{
    public function availability(Request $request)
    {
        $data = $request->validate([
            'professional_id' => 'required|exists:professionals,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $service = DB::table('services')
            ->where('id', $data['service_id'])
            ->where('professional_id', $data['professional_id'])
            ->where('active', true)
            ->first();

        if (!$service || $service->duration_minutes < 1) {
            abort(422, 'Servicio no disponible');
        }

        $slots = [];
        $day = Carbon::parse($data['date']);
        $now = now('America/Bogota');

        for ($minute = 9 * 60; $minute + $service->duration_minutes <= 18 * 60; $minute += 30) {
            $start = $day->copy()->startOfDay()->addMinutes($minute);
            $end = $start->copy()->addMinutes($service->duration_minutes);

            if ($start->lessThanOrEqualTo($now)) continue;

            $occupied = DB::table('appointments')
                ->where('professional_id', $data['professional_id'])
                ->where('status', 'confirmed')
                ->where('starts_at', '<', $end->format('Y-m-d H:i:s'))
                ->where('ends_at', '>', $start->format('Y-m-d H:i:s'))
                ->exists();

            if (!$occupied) $slots[] = $start->format('H:i');
        }

        return response()->json(['available' => $slots]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'professional_id' => 'required|exists:professionals,id',
            'service_id' => 'required|exists:services,id',
            'customer_name' => 'required|string|max:120',
            'customer_phone' => 'required|string|max:30',
            'starts_at' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $data['starts_at']);
        $service = DB::table('services')
            ->where('id', $data['service_id'])
            ->where('professional_id', $data['professional_id'])
            ->where('active', true)
            ->first();

        $professional = DB::table('professionals')
            ->where('id', $data['professional_id'])
            ->where('active', true)
            ->first();

        if (!$service || !$professional || $service->duration_minutes < 1) {
            abort(422, 'Profesional o servicio no disponible');
        }

        $end = $start->copy()->addMinutes($service->duration_minutes);
        $minute = $start->hour * 60 + $start->minute;

        if ($start->lessThanOrEqualTo(now('America/Bogota'))
            || $minute < 540
            || $minute % 30 !== 0
            || $end->toDateString() !== $start->toDateString()
            || $end->hour * 60 + $end->minute > 1080) {
            throw ValidationException::withMessages([
                'starts_at' => 'Horario fuera de disponibilidad',
            ]);
        }

        try {
            $id = DB::transaction(function () use ($data, $start, $end) {
                $occupied = DB::table('appointments')
                    ->where('professional_id', $data['professional_id'])
                    ->where('status', 'confirmed')
                    ->where('starts_at', '<', $end->format('Y-m-d H:i:s'))
                    ->where('ends_at', '>', $start->format('Y-m-d H:i:s'))
                    ->exists();

                if ($occupied) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'Este horario ya está reservado',
                    ]);
                }

                return DB::table('appointments')->insertGetId([
                    ...$data,
                    'ends_at' => $end->format('Y-m-d H:i:s'),
                    'status' => 'confirmed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) throw $e;
            report($e);
            abort(503, 'No se pudo confirmar. Intenta nuevamente.');
        }

        return response()->json([
            'message' => 'Reserva confirmada',
            'appointment_id' => $id,
        ], 201);
    }
}
