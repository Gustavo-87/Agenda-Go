<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TwoFactorShowCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:code {email : Correo del usuario con desafío pendiente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra el código 2FA pendiente (solo desarrollo local: recuperación segura cuando el correo no llega)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Usuario no encontrado.');

            return self::FAILURE;
        }

        $codigo = Cache::get('two_factor_'.$user->id);

        if ($codigo === null) {
            $this->warn('No hay un código pendiente para ese usuario. Inicia sesión para generar uno nuevo.');

            return self::FAILURE;
        }

        $this->info('Código 2FA pendiente (vence en 10 minutos): '.$codigo);

        return self::SUCCESS;
    }
}
