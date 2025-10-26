<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchLiveStatisticsJob;
use App\Jobs\FetchPreMatchLineupsJob;
use App\Jobs\UpdateFixtureStatusJob;
use App\Jobs\UpdatePeerAndTournamentTotalPoint;



Schedule::job(UpdateFixtureStatusJob::class)
    ->everyTwoMinutes();

Schedule::job(FetchPreMatchLineupsJob::class)
    ->everyTenMinutes();

Schedule::job(FetchLiveStatisticsJob::class)
    ->everyFiveMinutes();

Schedule::job(UpdatePeerAndTournamentTotalPoint::class)
    ->everyFiveMinutes();
