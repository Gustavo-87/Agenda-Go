<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'active', 'business_id'])]
class Professional extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'google_access_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'google_token_expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasMany<WorkSchedule, $this>
     */
    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class);
    }

    /**
     * @return HasMany<ScheduleException, $this>
     */
    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }
}
