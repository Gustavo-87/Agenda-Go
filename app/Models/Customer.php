<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id', 'name', 'phone', 'email', 'data_consent', 'data_consent_at',
    'commercial_consent', 'commercial_consent_at', 'absences',
])]
class Customer extends Model
{
    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_consent' => 'boolean',
            'data_consent_at' => 'datetime',
            'commercial_consent' => 'boolean',
            'commercial_consent_at' => 'datetime',
        ];
    }
}
