<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['business_id', 'starts_on', 'ends_on', 'amount', 'status'])]
class Membership extends Model
{
    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasMany<MembershipPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(MembershipPayment::class);
    }
}
