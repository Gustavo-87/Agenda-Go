<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Console\Command;

class ExpirePendingAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expira las solicitudes de reserva pendientes vencidas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Appointment::where('status', AppointmentStatus::Pending->value)
            ->where('pending_expires_at', '<=', now('America/Bogota'))
            ->update(['status' => AppointmentStatus::Expired->value]);

        $this->info("Solicitudes expiradas: {$count}");

        return self::SUCCESS;
    }
}
