<?php

namespace App\Jobs;

use App\Models\Professional;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncAppointmentToGoogleCalendar implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $appointmentId) {}

    public function handle(GoogleCalendarService $calendar): void
    {
        $appointment = DB::table('appointments')
            ->where('id', $this->appointmentId)
            ->where('status', 'confirmed')
            ->whereNull('google_event_id')
            ->first();

        if (! $appointment) {
            return;
        }

        $professional = Professional::find($appointment->professional_id);

        if (! $professional?->google_refresh_token) {
            return;
        }

        $service = DB::table('services')
            ->where('id', $appointment->service_id)
            ->first();

        $eventId = $calendar->createEvent(
            $professional,
            'AgendaGo - '.($service->name ?? 'Cita'),
            $appointment->starts_at,
            $appointment->ends_at
        );

        DB::table('appointments')
            ->where('id', $appointment->id)
            ->update([
                'google_event_id' => $eventId,
                'updated_at' => now(),
            ]);
    }
}
