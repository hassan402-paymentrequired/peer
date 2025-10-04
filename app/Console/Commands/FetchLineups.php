<?php

namespace App\Console\Commands;

use App\Jobs\FetchFixtureLineupsJob;
use App\Models\Fixture;
use Illuminate\Console\Command;

class FetchLineups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lineups:fetch {fixture_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch fixture lineups from Football API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fixtureId = $this->argument('fixture_id');

        if ($fixtureId) {
            // Fetch lineup for specific fixture
            $fixture = Fixture::where('external_id', $fixtureId)->first();

            if (!$fixture) {
                $this->error("Fixture with external ID {$fixtureId} not found");
                return 1;
            }

            $this->info("Fetching lineup for fixture {$fixtureId}...");
            FetchFixtureLineupsJob::dispatchSync($fixture->id);
            $this->info('Lineup fetch completed!');
        } else {
            
            $this->info('Dispatching lineup fetch job for all relevant fixtures...');
            FetchFixtureLineupsJob::dispatch();
            $this->info('Lineup fetch job dispatched!');

        }

        return 0;
    }
}
