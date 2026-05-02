<?php

namespace Seat\MumbleConnector\Models;

use Illuminate\Database\Eloquent\Model;

class MumbleGroupMapping extends Model
{
    protected $table = 'mumble_group_mappings';

    protected $fillable = [
        'seat_type',       // 'role', 'squad', 'corporation', 'alliance'
        'seat_identifier', // Role name, Squad ID, Corp ID, or Alliance ID
        'seat_name',       // Human-readable name for display
        'mumble_group',    // Mumble group name
        'name_tag',        // Optional tag appended to username e.g. " [FC]"
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for role mappings.
     */
    public function scopeRoles($query)
    {
        return $query->where('seat_type', 'role');
    }

    /**
     * Scope for squad mappings.
     */
    public function scopeSquads($query)
    {
        return $query->where('seat_type', 'squad');
    }

    /**
     * Scope for corporation mappings.
     */
    public function scopeCorporations($query)
    {
        return $query->where('seat_type', 'corporation');
    }

    /**
     * Scope for alliance mappings.
     */
    public function scopeAlliances($query)
    {
        return $query->where('seat_type', 'alliance');
    }

    /**
     * Get display name for the mapping source.
     */
    public function getDisplayNameAttribute(): string
    {
        return match ($this->seat_type) {
            'role' => "Role: {$this->seat_name}",
            'squad' => "Squad: {$this->seat_name}",
            'corporation' => "Corporation: {$this->seat_name}",
            'alliance' => "Alliance: {$this->seat_name}",
            default => $this->seat_identifier,
        };
    }
}
