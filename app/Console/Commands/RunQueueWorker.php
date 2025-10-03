<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunQueueWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:work-optimized';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run queue worker with optimized settings for long-running jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting optimized queue worker...');

        // Run queue worker with optimized settings
        $this->call('queue:work', [
            '--timeout' => 600,      // 10 minutes timeout
            '--memory' => 512,       // 512MB memory limit
            '--sleep' => 3,          // 3 seconds between jobs
            '--tries' => 3,          // 3 attempts per job
            '--max-jobs' => 100,     // Process 100 jobs then restart
            '--max-time' => 3600,    // Run for 1 hour then restart
        ]);

        return 0;
    }
}
