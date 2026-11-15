<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'sector', 'email', 'phone', 'active',
    'booking_min_notice_minutes', 'booking_max_advance_days',
    'booking_auto_confirm', 'booking_pending_ttl_minutes',
    'whatsapp_phone_number_id', 'messaging_included', 'messaging_overage_authorized',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Id of the business that owns the public booking channel
     * (WhatsApp bot, WhatsApp flows and reservation API).
     */
    public static function bookingChannelId(): ?int
    {
        return static::query()
            ->where('slug', (string) config('services.reservas.business_slug'))
            ->value('id');
    }

    /**
     * @return HasMany<Professional, $this>
     */
    public function professionals(): HasMany
    {
        return $this->hasMany(Professional::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<Holiday, $this>
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * RN-003 / RN-005 / RN-006: el negocio acepta reservas si está
     * activo y su membresía vigente no está suspendida, con cinco
     * días de gracia tras el vencimiento. Un negocio sin membresías
     * registradas conserva su comportamiento histórico.
     */
    public function puedeReservar(): bool
    {
        if (! $this->active) {
            return false;
        }

        $membresia = $this->memberships()->orderByDesc('ends_on')->first();

        if ($membresia === null) {
            return true;
        }

        // La membresía cancelada conserva el servicio hasta el fin
        // del período pagado (RF-MEM-006); la suspendida lo bloquea.
        return in_array($membresia->status, ['active', 'cancelled'], true)
            && now()->toDateString() <= Carbon::parse($membresia->ends_on)->addDays(5)->toDateString();
    }
}
