<?php

namespace App\Models;

use App\Enums\ScheduleExceptionKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['professional_id', 'kind', 'starts_on', 'ends_on', 'start_time', 'end_time'])]
class ScheduleException extends Model
{
    /**
     * @return BelongsTo<Professional, $this>
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ScheduleExceptionKind::class,
        ];
    }
}
