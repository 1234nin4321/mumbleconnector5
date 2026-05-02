<?php

namespace Seat\MumbleConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Seat\Web\Models\User;

class MumbleSyncLog extends Model
{
    protected $table = 'mumble_sync_logs';

    protected $fillable = [
        'seat_user_id',
        'action',
        'status',
        'message',
        'old_groups',
        'new_groups',
        'details',
    ];

    protected $casts = [
        'old_groups' => 'array',
        'new_groups' => 'array',
        'details' => 'array',
    ];

    /**
     * Get the SeAT user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seat_user_id');
    }

    /**
     * Log a sync action.
     */
    public static function logSync(
        int $userId,
        string $action,
        string $status,
        ?string $message = null,
        ?array $oldGroups = null,
        ?array $newGroups = null,
        ?array $details = null
    ): self {
        return self::create([
            'seat_user_id' => $userId,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'old_groups' => $oldGroups,
            'new_groups' => $newGroups,
            'details' => $details,
        ]);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => 'badge-success',
            'error' => 'badge-danger',
            'warning' => 'badge-warning',
            default => 'badge-secondary',
        };
    }
}
