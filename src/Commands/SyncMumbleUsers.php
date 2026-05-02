<?php

namespace Seat\MumbleConnector\Commands;

use Illuminate\Console\Command;
use Seat\MumbleConnector\Jobs\SyncAllMumbleUsers;

class SyncMumbleUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mumble:sync 
                            {--now : Run synchronously instead of queueing}';

    /**
     * The console command description.
     */
    protected $description = 'Sync all SeAT users to Mumble server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Mumble user sync...');

        if ($this->option('now')) {
            $this->info('Running synchronously...');
            app()->call([new SyncAllMumbleUsers(), 'handle']);
            $this->info('Sync completed!');
        } else {
            SyncAllMumbleUsers::dispatch();
            $this->info('Sync job queued.');
        }

        return Command::SUCCESS;
    }
}
