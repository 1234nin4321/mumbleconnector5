<?php

namespace Seat\MumbleConnector\Observers;

use Seat\MumbleConnector\Jobs\SyncSingleMumbleUser;
use Seat\MumbleConnector\Models\MumbleUser;
use Seat\Web\Models\User;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     * 
     * When a user's roles or squads change, queue a sync.
     */
    public function updated(User $user): void
    {
        if (!config('seat.mumble.sync.enabled', true)) {
            return;
        }

        // Check if user has a Mumble account
        $mumbleUser = MumbleUser::where('seat_user_id', $user->id)->first();
        
        if ($mumbleUser) {
            $mumbleUser->markForSync();
            SyncSingleMumbleUser::dispatch($user)->delay(now()->addMinutes(1));
        }
    }

    /**
     * Handle the User "deleted" event.
     * 
     * When a user is deleted from SeAT, remove from Mumble.
     */
    public function deleted(User $user): void
    {
        $mumbleUser = MumbleUser::where('seat_user_id', $user->id)->first();
        
        if ($mumbleUser) {
            // The foreign key with cascade will handle database cleanup
            // But we may want to actively remove from Mumble server
            app(\Seat\MumbleConnector\Services\MumbleService::class)->removeUser($mumbleUser);
        }
    }
}
