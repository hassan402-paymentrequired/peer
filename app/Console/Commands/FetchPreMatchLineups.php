<?php

namespace App\Console\Commands;

use App\Jobs\FetchPreMatchLineupsJob;
use Illuminate\Console\Command;

class FetchPreMatchLineups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lineups:fetch-prematch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch lineups for matches starting in 20-60 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching pre-match lineup fetch job...');

        FetchPreMatchLineupsJob::dispatch();

        $this->info('Pre-match lineup fetch job dispatched successfully!');

        return 0;
    }
}
