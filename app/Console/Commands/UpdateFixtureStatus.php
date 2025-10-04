<?php

namespace App\Console\Commands;

use App\Jobs\UpdateFixtureStatusJob;
use Illuminate\Console\Command;

class UpdateFixtureStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixtures:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update fixture status from Football API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching fixture status update job...');

        UpdateFixtureStatusJob::dispatch();

        $this->info('Fixture status update job dispatched successfully!');

        return 0;
    }
}
