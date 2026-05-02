<?php

namespace Seat\MumbleConnector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Seat\MumbleConnector\Services\MumbleService;
use Seat\Web\Models\User;

class SyncAllMumbleUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;

    /**
     * Execute the job.
     */
    public function handle(MumbleService $mumbleService): void
    {
        Log::info('Starting Mumble sync for all users');

        $users = User::with(['characters.affiliation', 'squads', 'roles'])->get();
        $synced = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                if ($mumbleService->syncUser($user)) {
                    $synced++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to sync user to Mumble', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Mumble sync completed', [
            'synced' => $synced,
            'failed' => $failed,
            'total' => $users->count(),
        ]);
    }
}
