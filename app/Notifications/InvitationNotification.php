<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Invitation $invitation,
        public string $token,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $enlace = route('invitations.accept', ['token' => $this->token]);

        return (new MailMessage)
            ->subject('Te invitamos a AgendaGo — '.$this->invitation->business?->name)
            ->line('Has sido invitado como '.($this->invitation->role === 'admin' ? 'administrador' : 'profesional').' de '.$this->invitation->business?->name.'.')
            ->line('El enlace caduca en 48 horas y solo puede usarse una vez.')
            ->action('Crear mi contraseña', $enlace)
            ->line('Si no esperabas esta invitación, ignora este mensaje.');
    }
}
