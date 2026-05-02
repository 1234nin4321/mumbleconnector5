<?php

namespace Seat\MumbleConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Seat\Web\Models\User;

class MumbleTemporaryLink extends Model
{
    protected $table = 'mumble_temporary_links';

    protected $fillable = [
        'token',
        'display_name',
        'mumble_username',
        'password',
        'mumble_user_id',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who created the link.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the link is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
