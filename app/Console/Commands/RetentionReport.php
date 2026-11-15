<?php

namespace App\Console\Commands;

use App\Models\Business;
use Illuminate\Console\Command;

class RetentionReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retention:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista negocios cuya conservación de datos (90 días tras cancelar) venció; nunca elimina datos sin validación';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limite = now()->subDays(90)->toDateString();

        $negocios = Business::whereHas('memberships', function ($query) use ($limite) {
            $query->where('status', 'cancelled')->where('ends_on', '<', $limite);
        })->get();

        foreach ($negocios as $negocio) {
            $fin = $negocio->memberships()->where('status', 'cancelled')->max('ends_on');

            $this->info(sprintf(
                'Negocio %s: retención vencida (período terminó el %s). Requiere validación antes de purga; no se elimina automáticamente.',
                $negocio->name,
                $fin
            ));
        }

        $this->info('Negocios con retención vencida: '.$negocios->count());

        return self::SUCCESS;
    }
}
