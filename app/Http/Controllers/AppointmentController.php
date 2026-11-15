<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Jobs\EnviarNotificacionWhatsApp;
use App\Jobs\SyncAppointmentToGoogleCalendar;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class AppointmentController extends Controller
{
    public function __construct(private DisponibilidadService $disponibilidad) {}

    public function availability(Request $request)
    {
        $data = $request->validate([
            'professional_id' => 'required|exists:professionals,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        [$professional, $service] = $this->recursoDelCanal(
            (int) $data['professional_id'],
            (int) $data['service_id']
        );

        if (! $service->active || $service->duration_minutes < 1) {
            abort(422, 'Servicio no disponible');
        }

        return response()->json([
            'available' => $this->disponibilidad->slots($professional, $service, $data['date']),
        ]);
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

        [$professional, $service] = $this->recursoDelCanal(
            (int) $data['professional_id'],
            (int) $data['service_id']
        );

        if (! $service->active || ! $professional->active || $service->duration_minutes < 1) {
            abort(422, 'Profesional o servicio no disponible');
        }

        $business = $professional->business;

        if ($business === null || ! $business->puedeReservar()) {
            throw ValidationException::withMessages([
                'professional_id' => 'El negocio no está habilitado para nuevas reservas.',
            ]);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $data['starts_at']);
        $end = $start->copy()->addMinutes((int) $service->duration_minutes);

        if (! in_array($start->format('H:i'), $this->disponibilidad->slots($professional, $service, $start->toDateString()), true)) {
            throw ValidationException::withMessages(['starts_at' => 'Horario fuera de disponibilidad']);
        }

        try {
            $customer = Customer::updateOrCreate(
                ['business_id' => $business->id, 'phone' => $data['customer_phone']],
                ['name' => $data['customer_name']]
            );

            $id = DB::transaction(function () use ($data, $professional, $service, $business, $customer, $start, $end) {
                if ($this->disponibilidad->estaOcupado($professional, $start, $end)) {
                    throw ValidationException::withMessages(['starts_at' => 'Este horario ya está reservado']);
                }

                $confirmada = (bool) $business->booking_auto_confirm;

                return DB::table('appointments')->insertGetId([
                    'professional_id' => $professional->id,
                    'service_id' => $service->id,
                    'business_id' => $business->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'starts_at' => $start->format('Y-m-d H:i:s'),
                    'ends_at' => $end->format('Y-m-d H:i:s'),
                    'status' => $confirmada
                        ? AppointmentStatus::Confirmed->value
                        : AppointmentStatus::Pending->value,
                    'pending_expires_at' => $confirmada
                        ? null
                        : now()->addMinutes((int) $business->booking_pending_ttl_minutes),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            abort(503, 'No se pudo confirmar. Intenta nuevamente.');
        }

        SyncAppointmentToGoogleCalendar::dispatch($id);

        if ($customer->absences >= 2 && $professional->phone !== null) {
            EnviarNotificacionWhatsApp::dispatch(
                $professional->phone,
                'Alerta: la cliente '.$customer->name.' tiene '.$customer->absences.' ausencias registradas.',
                $business->id
            );
        }

        return response()->json([
            'message' => 'Reserva confirmada',
            'appointment_id' => $id,
        ], 201);
    }

    public function confirm(Request $request, Appointment $appointment)
    {
        $this->assertPuedeGestionarCita($request->user(), $appointment);

        if ($appointment->status !== AppointmentStatus::Pending->value) {
            throw ValidationException::withMessages([
                'status' => 'Solo las solicitudes pendientes pueden confirmarse.',
            ]);
        }

        $appointment->update([
            'status' => AppointmentStatus::Confirmed->value,
            'pending_expires_at' => null,
        ]);

        EnviarNotificacionWhatsApp::dispatch(
            $appointment->customer_phone,
            'Tu cita fue confirmada para '.$appointment->starts_at.'.',
            $appointment->business_id
        );

        return response()->json(['message' => 'Reserva confirmada']);
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $this->assertPuedeGestionarCita($request->user(), $appointment);

        if (! in_array($appointment->status, [
            AppointmentStatus::Pending->value,
            AppointmentStatus::Confirmed->value,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Esta reserva ya no puede cancelarse.',
            ]);
        }

        $appointment->update([
            'status' => AppointmentStatus::Cancelled->value,
            'pending_expires_at' => null,
        ]);

        EnviarNotificacionWhatsApp::dispatch(
            $appointment->customer_phone,
            'Tu cita fue cancelada. Si deseas reprogramarla, escríbenos.',
            $appointment->business_id
        );

        return response()->json(['message' => 'Reserva cancelada']);
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->assertPuedeGestionarCita($request->user(), $appointment);

        if (! in_array($appointment->status, [
            AppointmentStatus::Pending->value,
            AppointmentStatus::Confirmed->value,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Esta reserva no puede reprogramarse.',
            ]);
        }

        $data = $request->validate(['starts_at' => 'required|date_format:Y-m-d H:i:s']);

        $professional = $appointment->professional;
        $service = $appointment->service;
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $data['starts_at']);
        $end = $start->copy()->addMinutes((int) $service->duration_minutes);

        if (! in_array($start->format('H:i'), $this->disponibilidad->slots($professional, $service, $start->toDateString()), true)) {
            throw ValidationException::withMessages(['starts_at' => 'Horario fuera de disponibilidad']);
        }

        if ($this->disponibilidad->estaOcupado($professional, $start, $end, $appointment->id)) {
            throw ValidationException::withMessages(['starts_at' => 'Este horario ya está reservado']);
        }

        $business = $professional->business;
        $pendiente = $appointment->status === AppointmentStatus::Pending->value;

        $appointment->update([
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $end->format('Y-m-d H:i:s'),
            'pending_expires_at' => $pendiente
                ? now()->addMinutes((int) $business->booking_pending_ttl_minutes)
                : null,
        ]);

        return response()->json(['message' => 'Reserva reprogramada']);
    }

    /**
     * Registro de asistencia: confirmada → completada.
     */
    public function attend(Request $request, Appointment $appointment)
    {
        $this->assertPuedeGestionarCita($request->user(), $appointment);

        if ($appointment->status !== AppointmentStatus::Confirmed->value) {
            throw ValidationException::withMessages([
                'status' => 'Solo las citas confirmadas registran asistencia.',
            ]);
        }

        $appointment->update(['status' => AppointmentStatus::Completed->value]);

        return response()->json(['message' => 'Asistencia confirmada']);
    }

    /**
     * Registro de ausencia: confirmada → ausente, con acumulado
     * en la ficha del cliente (RN-027).
     */
    public function absent(Request $request, Appointment $appointment)
    {
        $this->assertPuedeGestionarCita($request->user(), $appointment);

        if ($appointment->status !== AppointmentStatus::Confirmed->value) {
            throw ValidationException::withMessages([
                'status' => 'Solo las citas confirmadas registran ausencias.',
            ]);
        }

        $appointment->update(['status' => AppointmentStatus::Absent->value]);

        if ($appointment->customer_id !== null) {
            Customer::whereKey($appointment->customer_id)->increment('absences');
        }

        return response()->json(['message' => 'Ausencia registrada']);
    }

    /**
     * El usuario vinculado al profesional o quien gestione su negocio.
     */
    private function assertPuedeGestionarCita(User $user, Appointment $appointment): void
    {
        $professional = $appointment->professional;

        $autorizado = $professional !== null
            && ($user->can('manage', $professional) || $professional->user_id === $user->id);

        abort_unless($autorizado, 403);
    }

    /**
     * Resolve the professional and service of a booking, rejecting
     * resources outside the booking channel's business or mixed
     * across businesses.
     *
     * @return array{0: Professional, 1: Service}
     */
    private function recursoDelCanal(int $professionalId, int $serviceId): array
    {
        $businessId = Business::bookingChannelId();

        $professional = Professional::find($professionalId);

        $service = Service::whereKey($serviceId)
            ->where('professional_id', $professionalId)
            ->first();

        if (! $professional || ! $service
            || $professional->business_id != $businessId
            || $service->business_id != $professional->business_id) {
            abort(422, 'Profesional o servicio no disponible');
        }

        return [$professional, $service];
    }
}
