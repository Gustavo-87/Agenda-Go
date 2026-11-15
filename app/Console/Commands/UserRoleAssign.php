<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserRoleAssign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:role {email : Correo del usuario} {role : super_admin|admin|professional}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna un rol a un usuario existente (solo desarrollo local: por ejemplo, promover al propietario a superadministrador)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $rol = $this->argument('role');

        if (! in_array($rol, ['super_admin', 'admin', 'professional'], true)) {
            $this->error('Rol inválido. Usa: super_admin, admin o professional.');

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Usuario no encontrado.');

            return self::FAILURE;
        }

        $user->update(['role' => $rol]);

        $this->info("Usuario {$user->email} actualizado al rol {$rol}.");

        return self::SUCCESS;
    }
}
