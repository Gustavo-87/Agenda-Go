<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ScheduleException;
use App\Models\WhatsAppMessage;
use App\Models\WorkSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetentionPurge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retention:purge
        {slug : Slug del negocio a purgar}
        {--confirm= : Autorización explícita: debe coincidir con el slug del negocio}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purga datos operativos tras 90 días de la cancelación (conserva registros financieros y auditoría); requiere confirmación explícita';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $business = Business::where('slug', (string) $this->argument('slug'))->first();

        if ($business === null) {
            $this->error('Negocio no encontrado.');

            return self::FAILURE;
        }

        if ($this->option('confirm') !== $business->slug) {
            $this->error('Purga no autorizada: use --confirm='.$business->slug.' (autorización explícita).');

            return self::FAILURE;
        }

        if ($business->legal_hold) {
            $this->error('Exclusión legal activa: el negocio no puede purgarse.');

            return self::FAILURE;
        }

        $limite = now()->subDays(90)->toDateString();
        $elegible = $business->memberships()->where('status', 'cancelled')->where('ends_on', '<', $limite)->exists()
            && ! $business->memberships()->where('status', 'active')->exists();

        if (! $elegible) {
            $this->error('El negocio no cumple la política de retención (90 días desde la cancelación).');

            return self::FAILURE;
        }

        $professionalIds = $business->professionals()->pluck('id');

        $conteos = DB::transaction(function () use ($business, $professionalIds) {
            $conteos = [
                'citas' => Appointment::where('business_id', $business->id)->delete(),
                'clientes' => Customer::where('business_id', $business->id)->delete(),
                'mensajes' => WhatsAppMessage::where('business_id', $business->id)->delete(),
                'horarios' => WorkSchedule::whereIn('professional_id', $professionalIds)->delete(),
                'excepciones' => ScheduleException::whereIn('professional_id', $professionalIds)->delete(),
            ];

            // Conservación por obligaciones legales: registros
            // financieros, membresías, usuarios y auditoría.
            return $conteos;
        });

        AuditLog::registrar(null, 'negocio.purga', $business, $conteos);

        $this->info('Purga completada (datos operativos eliminados, registros financieros conservados): '.json_encode($conteos));

        return self::SUCCESS;
    }
}
