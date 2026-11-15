<?php

namespace App\Providers;

use App\Models\MailDelivery;
use Carbon\Carbon;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        \Illuminate\Support\Carbon::setLocale(config('app.locale'));

        Event::listen(
            NotificationSending::class,
            function ($event): void {
                if ($event->channel !== 'mail') {
                    return;
                }

                MailDelivery::create([
                    'notification' => $event->notification::class,
                    'recipient' => $this->destinatario($event->notifiable),
                    'status' => 'registered',
                ]);
            }
        );

        Event::listen(
            NotificationSent::class,
            function ($event): void {
                if ($event->channel !== 'mail') {
                    return;
                }

                $this->ultima($event->notification::class, $this->destinatario($event->notifiable))
                    ?->update(['status' => 'accepted', 'sent_at' => now()]);
            }
        );

        Event::listen(
            NotificationFailed::class,
            function ($event): void {
                if ($event->channel !== 'mail') {
                    return;
                }

                $razon = $event->data;

                $error = $razon instanceof \Throwable
                    ? $razon->getMessage()
                    : (string) (is_array($razon) ? json_encode($razon) : (string) $razon);

                $this->ultima($event->notification::class, $this->destinatario($event->notifiable))
                    ?->update(['status' => 'failed', 'error' => $error]);
            }
        );
    }

    private function destinatario(object $notifiable): string
    {
        $ruta = $notifiable->routeNotificationFor('mail');

        return implode(',', array_filter((array) $ruta));
    }

    private function ultima(string $notification, string $recipient): ?MailDelivery
    {
        return MailDelivery::where('notification', $notification)
            ->where('recipient', $recipient)
            ->where('status', 'registered')
            ->latest('id')
            ->first();
    }
}
