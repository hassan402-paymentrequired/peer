<?php

namespace App\Http\Controllers\Api\Fixture;

use App\Enum\FixtureStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\FetchWeeklyFixtures;
use App\Models\Fixture;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class FixtureController extends Controller
{
    public function index()
    {
        $fixtures = Fixture::orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->respondWithCustomData([
            'fixtures' => $fixtures
        ], 200);
    }

    public function activeFixtures()
    {
        $fixtures = Fixture::whereIn('status', [
            FixtureStatusEnum::NOT_STARTED->value,
            FixtureStatusEnum::FIRST_HALF->value,
            FixtureStatusEnum::SECOND_HALF->value,
            FixtureStatusEnum::SECOND_HALF_STARTED->value,
            FixtureStatusEnum::PAUSED->value,
            FixtureStatusEnum::HALFTIME->value,
            FixtureStatusEnum::EXTRA_TIME->value,
            FixtureStatusEnum::PENALTY_IN_PROGRESS->value,
            FixtureStatusEnum::BREAK_TIME->value,
            FixtureStatusEnum::KICK_OFF->value,
            FixtureStatusEnum::IN_PROGRESS->value,
        ])
            ->get();

        return $this->respondWithCustomData([
            'fixtures' => $fixtures
        ], 200);
    }

    public function refetch(Request $request)
    {
        $league = $request->league;
        $season = $request->season ?? '2025';
        $from = Carbon::parse($request->from)->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');
        $to = Carbon::parse($request->to)->format('Y-m-d') ?? Carbon::now()->addDays(7)->format('Y-m-d');


        FetchWeeklyFixtures::dispatch($league, $season, $to, $from);

        return $this->respondWithCustomData([
            'message' => 'Fixtures refetched successfully'
        ], 200);
    }
}
