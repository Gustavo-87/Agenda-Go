<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'action', 'auditable_type', 'auditable_id', 'details'])]
class AuditLog extends Model
{
    /**
     * Registra una operación relevante para auditoría.
     */
    public static function registrar(?User $user, string $action, ?Model $model = null, array $details = []): void
    {
        static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $model === null ? null : $model::class,
            'auditable_id' => $model?->getKey(),
            'details' => $details === [] ? null : json_encode($details),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
