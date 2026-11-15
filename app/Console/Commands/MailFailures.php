<?php

namespace App\Console\Commands;

use App\Models\MailDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MailFailures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:failures {--limit=50 : Máximo de registros a mostrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista los envíos de correo fallidos registrados (invitaciones, OTP y recuperación)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fallos = MailDelivery::where('status', 'failed')
            ->latest('id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($fallos->isEmpty()) {
            $this->info('Sin envíos fallidos registrados.');

            return self::SUCCESS;
        }

        $this->table(
            ['Registro', 'Notificación', 'Destinatario', 'Error', 'Fecha'],
            $fallos->map(fn (MailDelivery $delivery) => [
                $delivery->id,
                $delivery->notification,
                $delivery->recipient,
                Str::limit((string) $delivery->error, 60),
                $delivery->created_at->format('Y-m-d H:i'),
            ])
        );

        $this->info('Envíos fallidos: '.$fallos->count().'. Reintenta con los mecanismos seguros: reenvío de invitaciones, nuevo inicio de sesión para OTP o nueva solicitud de recuperación.');

        return self::SUCCESS;
    }
}
