<?php

namespace App\Console\Commands;

use App\Jobs\FetchLiveStatisticsJob;
use Illuminate\Console\Command;

class ScheduleLiveStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistics:fetch-live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch live player statistics from Football API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching FetchLiveStatisticsJob...');

        FetchLiveStatisticsJob::dispatch();

        $this->info('Job dispatched successfully!');

        return 0;
    }
}
