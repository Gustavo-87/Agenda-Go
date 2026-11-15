<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : Destinatario de la prueba de envío}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba controlado por el transporte configurado, sin exponer credenciales ni tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            Mail::raw(
                'Este es un correo de prueba de AgendaGo. Si lo recibes, el transporte de correo está funcionando.',
                function ($message): void {
                    $message->to($this->argument('email'))
                        ->subject('Prueba de correo — AgendaGo');
                }
            );
        } catch (Throwable $e) {
            $this->error('El proveedor de correo rechazó o no aceptó el envío: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Mensaje aceptado por el proveedor de correo para '.$this->argument('email').'.');
        $this->line('Nota: la aceptación por SMTP no garantiza la entrega final; esa la confirma el proveedor.');

        return self::SUCCESS;
    }
}
