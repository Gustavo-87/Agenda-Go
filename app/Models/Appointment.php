<?php

namespace App\Models;

use App\Jobs\EnviarNotificacionWhatsApp;
use App\Support\FormatoHora;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['starts_at', 'ends_at', 'status', 'pending_expires_at', 'customer_id', 'reminder_sent_at'])]
class Appointment extends Model
{
    /**
     * Cancelación por el cliente (canales WhatsApp y Flows): solo
     * solicitudes vigentes o citas confirmadas, antes del inicio;
     * libera la disponibilidad y avisa al profesional.
     */
    public function cancelarPorCliente(): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed'], true)
            || $this->starts_at <= now('America/Bogota')->format('Y-m-d H:i:s')) {
            return false;
        }

        $this->update(['status' => 'cancelled', 'pending_expires_at' => null]);

        $resumen = FormatoHora::fechaHora($this->starts_at).' — '.($this->service?->name ?? 'Servicio');

        AppNotification::registrar(
            $this->business,
            null,
            'cita',
            'Cita cancelada por el cliente: '.$resumen.'.'
        );

        if ($this->professional?->phone !== null) {
            EnviarNotificacionWhatsApp::dispatch(
                $this->professional->phone,
                'Cita cancelada por el cliente: '.$resumen.'.',
                $this->business_id
            );
        }

        return true;
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Professional, $this>
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pending_expires_at' => 'datetime',
        ];
    }
}
