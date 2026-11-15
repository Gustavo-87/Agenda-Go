<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea una copia de seguridad local de la base de datos (ver docs/despliegue.md)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $conexion = config('database.default');
        $ruta = config("database.connections.{$conexion}.database");

        if (! is_string($ruta) || ! is_file($ruta)) {
            $this->error('La conexión actual no usa un archivo local. Copias remotas: ver docs/despliegue.md.');

            return self::FAILURE;
        }

        $destino = storage_path('app/backups/agenda-'.now()->format('Ymd-His').'.sqlite');
        File::ensureDirectoryExists(dirname($destino));
        copy($ruta, $destino);

        $this->info('Copia creada: '.$destino);

        return self::SUCCESS;
    }
}
