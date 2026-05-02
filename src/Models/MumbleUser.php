<?php

namespace Seat\MumbleConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Seat\Web\Models\User;

class MumbleUser extends Model
{
    protected $table = 'mumble_users';

    protected $fillable = [
        'seat_user_id',
        'mumble_username',      // Stable character name — never changes
        'mumble_display_name',  // Formatted name with ticker/tags pushed to Mumble server
        'mumble_user_id',
        'password_hash',
        'groups',
        'last_sync',
        'needs_sync',
        'is_active',
    ];

    protected $casts = [
        'groups' => 'array',
        'last_sync' => 'datetime',
        'needs_sync' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the SeAT user.
     */
    public function seatUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seat_user_id');
    }

    /**
     * Scope for users needing sync.
     */
    public function scopeNeedsSync($query)
    {
        return $query->where('needs_sync', true);
    }

    /**
     * Scope for active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Mark user as needing sync.
     */
    public function markForSync(): void
    {
        $this->update(['needs_sync' => true]);
    }

    /**
     * Mark sync as complete.
     */
    public function syncComplete(): void
    {
        $this->update([
            'needs_sync' => false,
            'last_sync' => now(),
        ]);
    }

    /**
     * Get formatted groups string.
     */
    public function getGroupsStringAttribute(): string
    {
        return implode(', ', $this->groups ?? []);
    }
}
