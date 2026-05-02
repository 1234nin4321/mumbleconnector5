<?php

namespace Seat\MumbleConnector\Commands;

use Illuminate\Console\Command;
use Seat\MumbleConnector\Models\MumbleUser;
use Seat\MumbleConnector\Models\MumbleGroupMapping;
use Seat\MumbleConnector\Services\MumbleService;
use Seat\Web\Models\User;

class SyncMumbleGroups extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mumble:sync-groups 
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate and sync all Mumble group memberships';

    /**
     * Execute the console command.
     */
    public function handle(MumbleService $mumbleService): int
    {
        $this->info('Calculating Mumble groups for all users...');

        $users = User::with(['characters.affiliation', 'squads', 'roles'])->get();
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $table = [];

        foreach ($users as $user) {
            $currentGroups = MumbleUser::where('seat_user_id', $user->id)->first()?->groups ?? [];
            $newGroups = $mumbleService->calculateUserGroups($user);

            $added = array_diff($newGroups, $currentGroups);
            $removed = array_diff($currentGroups, $newGroups);

            if (!empty($added) || !empty($removed)) {
                $table[] = [
                    $user->name,
                    implode(', ', $added) ?: '-',
                    implode(', ', $removed) ?: '-',
                ];
            }
        }

        if (empty($table)) {
            $this->info('No group changes needed.');
            return Command::SUCCESS;
        }

        $this->table(['User', 'Groups Added', 'Groups Removed'], $table);

        if (!$dryRun) {
            $this->info('Syncing group changes...');
            
            foreach ($users as $user) {
                $mumbleService->syncUser($user);
            }

            $this->info('Group sync completed!');
        }

        return Command::SUCCESS;
    }
}
