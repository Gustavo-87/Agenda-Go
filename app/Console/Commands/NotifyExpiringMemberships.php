<?php

namespace App\Console\Commands;

use App\Jobs\EnviarNotificacionWhatsApp;
use App\Models\Membership;
use Illuminate\Console\Command;

class NotifyExpiringMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memberships:notify-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Avisa por WhatsApp los vencimientos de membresía en los próximos 5 días (RN-010)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hoy = now()->toDateString();
        $limite = now()->addDays(5)->toDateString();
        $avisados = 0;

        foreach (Membership::where('status', 'active')
            ->whereBetween('ends_on', [$hoy, $limite])
            ->get() as $membresia) {
            $renovada = Membership::where('business_id', $membresia->business_id)
                ->where('ends_on', '>', $membresia->ends_on)
                ->exists();

            if ($renovada) {
                continue;
            }

            $negocio = $membresia->business;

            if ($negocio === null || $negocio->phone === null) {
                continue;
            }

            EnviarNotificacionWhatsApp::dispatch(
                $negocio->phone,
                'Tu membresía de AgendaGo vence el '.$membresia->ends_on.'. '
                .'Renueva para mantener las reservas habilitadas.',
                $negocio->id
            );

            $avisados++;
        }

        $this->info("Avisos de vencimiento enviados: {$avisados}");

        return self::SUCCESS;
    }
}
