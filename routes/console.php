<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchLiveStatisticsJob;
use App\Jobs\FetchPreMatchLineupsJob;
use App\Jobs\UpdateFixtureStatusJob;
use App\Jobs\UpdatePeerAndTournamentTotalPoint;
use App\Jobs\UpdateTournamentTotalPoint;

Schedule::job(UpdateFixtureStatusJob::class)
    ->everyTwoMinutes();

Schedule::job(FetchPreMatchLineupsJob::class)
    ->everyFiveMinutes();

Schedule::job(FetchLiveStatisticsJob::class)
    ->everyMinute();

// Schedule::job(UpdatePeerAndTournamentTotalPoint::class)
//     ->everyTwoMinutes();

// Schedule::job(UpdateTournamentTotalPoint::class)
//     ->everyTwoMinutes();
