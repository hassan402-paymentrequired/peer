<?php

namespace App\Console;

use App\Jobs\FetchLiveStatisticsJob;
use App\Jobs\FetchPreMatchLineupsJob;
use App\Jobs\UpdateFixtureStatusJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Update fixture status every 2 minutes (to catch status changes quickly)
        $schedule->job(UpdateFixtureStatusJob::class)
            ->everyTwoMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Fetch pre-match lineups every 10 minutes (20-60 minutes before kickoff)
        $schedule->job(FetchPreMatchLineupsJob::class)
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Fetch live statistics every 5 minutes during match days
        $schedule->job(FetchLiveStatisticsJob::class)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
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
