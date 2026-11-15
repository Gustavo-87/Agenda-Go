<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Holiday;
use Illuminate\Console\Command;

class ImportHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:import
        {file : Archivo CSV con líneas fecha,nombre (AAAA-MM-DD,Nombre)}
        {--business= : Slug del negocio (si se omite, importa a todos)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa festivos (por ejemplo, los festivos colombianos oficiales) con bloqueo por defecto en la disponibilidad';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ruta = (string) $this->argument('file');

        if (! is_readable($ruta)) {
            $this->error('El archivo no existe o no es legible.');

            return self::FAILURE;
        }

        $businesses = $this->option('business')
            ? Business::where('slug', (string) $this->option('business'))->get()
            : Business::all();

        if ($businesses->isEmpty()) {
            $this->error('No se encontraron negocios para importar.');

            return self::FAILURE;
        }

        $lineas = 0;

        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            [$fecha, $nombre] = array_pad(explode(',', $linea, 2), 2, null);

            if (! preg_match('~^\d{4}-\d{2}-\d{2}$~', trim((string) $fecha))) {
                $this->warn('Línea ignorada (formato inválido): '.$linea);

                continue;
            }

            foreach ($businesses as $business) {
                Holiday::updateOrCreate(
                    ['business_id' => $business->id, 'date' => trim((string) $fecha)],
                    ['name' => trim((string) $nombre) ?: null]
                );
            }

            $lineas++;
        }

        $this->info("Festivos importados: {$lineas} para {$businesses->count()} negocio(s).");

        return self::SUCCESS;
    }
}
