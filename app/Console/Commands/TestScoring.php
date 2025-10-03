<?php

namespace App\Console\Commands;

use App\Jobs\CalculateCompetitionScoresJob;
use App\Models\Tournament;
use App\Models\Peer;
use Illuminate\Console\Command;

class TestScoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scoring:test {type} {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test scoring calculation for tournament or peer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $id = $this->argument('id');

        if (!in_array($type, ['tournament', 'peer'])) {
            $this->error('Type must be either "tournament" or "peer"');
            return 1;
        }

        if ($type === 'tournament') {
            $competition = Tournament::find($id);
            if (!$competition) {
                $this->error("Tournament with ID {$id} not found");
                return 1;
            }
        } else {
            $competition = Peer::find($id);
            if (!$competition) {
                $this->error("Peer with ID {$id} not found");
                return 1;
            }
        }

        $this->info("Dispatching scoring job for {$type} {$id}...");

        CalculateCompetitionScoresJob::dispatch($type, $id);

        $this->info('Scoring job dispatched successfully!');

        return 0;
    }
}
