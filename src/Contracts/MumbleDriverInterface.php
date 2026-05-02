<?php

namespace Seat\MumbleConnector\Contracts;

use Seat\MumbleConnector\Models\MumbleUser;

interface MumbleDriverInterface
{
    /**
     * Test the connection to the Mumble server.
     */
    public function testConnection(): array;

    /**
     * Get all users from the Mumble server.
     */
    public function getUsers(): array;

    /**
     * Get all groups from the Mumble server.
     */
    public function getGroups(): array;

    /**
     * Create or update a user on the Mumble server.
     */
    public function syncUser(MumbleUser $user, array $groups): bool;

    /**
     * Remove a user from the Mumble server.
     */
    public function removeUser(MumbleUser $user): bool;



    /**
     * Add a user to a group.
     */
    public function addUserToGroup(MumbleUser $user, string $group): bool;

    /**
     * Remove a user from a group.
     */
    public function removeUserFromGroup(MumbleUser $user, string $group): bool;

    /**
     * Get server info (name, version, connected users, etc.)
     */
    public function getServerInfo(): array;
}
