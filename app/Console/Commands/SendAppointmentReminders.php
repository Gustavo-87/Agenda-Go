<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Jobs\EnviarNotificacionWhatsApp;
use App\Models\Appointment;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios de 24 horas para las próximas citas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $citas = Appointment::where('status', AppointmentStatus::Confirmed->value)
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addHours(24))
            ->get();

        foreach ($citas as $cita) {
            EnviarNotificacionWhatsApp::dispatch(
                $cita->customer_phone,
                'Recordatorio: tu cita es el '.$cita->starts_at.'. Si no puedes asistir, avísanos con anticipación.',
                $cita->business_id
            );

            $cita->update(['reminder_sent_at' => now()]);
        }

        $this->info('Recordatorios enviados: '.$citas->count());

        return self::SUCCESS;
    }
}
