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

class SyncSingleMumbleUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(MumbleService $mumbleService): void
    {
        Log::info('Syncing single user to Mumble', ['user_id' => $this->user->id]);

        try {
            $mumbleService->syncUser($this->user);
        } catch (\Exception $e) {
            Log::error('Failed to sync user to Mumble', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
