<?php

namespace App\Console;

use App\Jobs\FetchLiveStatisticsJob;
use App\Jobs\FetchPreMatchLineupsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Fetch pre-match lineups every 15 minutes (to catch lineups when they become available)
        $schedule->job(FetchPreMatchLineupsJob::class)
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Fetch live statistics every 5 minutes during match days
        $schedule->job(FetchLiveStatisticsJob::class)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lineups:fetch')->everyFiveSeconds();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
