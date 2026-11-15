<?php

namespace App\Jobs;

use App\Models\AppNotification;
use App\Models\Business;
use App\Models\WhatsAppMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Notificación saliente por WhatsApp con reintentos. La entrega
 * real depende de la configuración de Meta por negocio.
 */
class EnviarNotificacionWhatsApp implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    public function __construct(
        public string $telefono,
        public string $mensaje,
        public ?int $businessId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $business = $this->businessId !== null ? Business::find($this->businessId) : null;

        $phoneNumberId = $business?->whatsapp_phone_number_id
            ?? config('services.whatsapp.phone_number_id');

        $respuesta = Http::withToken(config('services.whatsapp.access_token'))
            ->post("https://graph.facebook.com/v27.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->telefono,
                'type' => 'text',
                'text' => ['body' => $this->mensaje],
            ]);

        WhatsAppMessage::create([
            'business_id' => $business?->id,
            'direction' => 'outbound',
            'phone' => $this->telefono,
            'body' => $this->mensaje,
            'status' => $respuesta->successful() ? 'sent' : 'failed',
        ]);

        if ($respuesta->failed()) {
            AppNotification::registrar(
                $business,
                null,
                'error',
                'No se pudo enviar el mensaje de WhatsApp a '.$this->telefono.'.'
            );

            throw new RuntimeException('No se pudo enviar el mensaje de WhatsApp.');
        }
    }
}
