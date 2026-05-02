<?php

namespace Seat\MumbleConnector\Commands;

use Illuminate\Console\Command;
use Seat\MumbleConnector\Models\MumbleTemporaryLink;
use Seat\MumbleConnector\Models\MumbleUser;
use Seat\MumbleConnector\Services\MumbleService;

class CleanupMumbleGuestLinks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mumble:cleanup-guests';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired temporary guest links and remove them from Mumble';

    /**
     * Execute the console command.
     */
    public function handle(MumbleService $mumbleService): int
    {
        $expiredLinks = MumbleTemporaryLink::where('expires_at', '<=', now())->get();

        if ($expiredLinks->isEmpty()) {
            $this->info('No expired guest links found.');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d expired guest links. Cleaning up...', $expiredLinks->count()));

        $driver = $mumbleService->getDriver();

        foreach ($expiredLinks as $link) {
            if ($link->mumble_user_id) {
                try {
                    $this->info(sprintf('Removing guest user: %s (ID: %d)', $link->mumble_username, $link->mumble_user_id));
                    
                    // Mock a MumbleUser for the driver
                    $fakeUser = new MumbleUser([
                        'mumble_user_id' => $link->mumble_user_id,
                    ]);
                    
                    $driver->removeUser($fakeUser);
                } catch (\Exception $e) {
                    $this->error(sprintf('Failed to remove guest user %s: %s', $link->mumble_username, $e->getMessage()));
                }
            }

            $link->delete();
        }

        $this->info('Cleanup completed.');

        return Command::SUCCESS;
    }
}
