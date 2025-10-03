<?php

namespace App\Utils\Services\Player;

use App\Models\Player;
use App\Models\PlayerMatch;
use App\Models\Fixture;
use App\Models\FixtureLineup;
use App\Jobs\FetchFixtureLineupsJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PlayerAvailabilityService
{
    /**
     * Get available players for a specific fixture (only those in the lineup)
     */
    public function getAvailablePlayersForFixture(int $fixtureId): Collection
    {
        $fixture = Fixture::findOrFail($fixtureId);

        // First, ensure we have lineup data for this fixture
        $this->ensureLineupData($fixture);

        // Get all players in the lineups for this fixture
        $playersInLineup = $this->getPlayersInFixtureLineup($fixture->id);

        if (empty($playersInLineup)) {
            // If no lineup data available, return empty collection
            return collect([]);
        }

        // Filter out players who already have matches created for this fixture
        $unavailablePlayerIds = PlayerMatch::where('fixture_id', $fixture->id)
            ->pluck('player_id')
            ->toArray();

        $availablePlayerIds = array_diff($playersInLineup, $unavailablePlayerIds);

        return Player::whereIn('external_id', $availablePlayerIds)
            ->where('status', true)
            ->with(['team'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get players who are in the lineup for a specific fixture
     */
    public function getPlayersInFixtureLineup(int $fixtureId): array
    {
        $lineups = FixtureLineup::where('fixture_id', $fixtureId)->get();

        $playersInLineup = [];

        foreach ($lineups as $lineup) {
            $playersInLineup = array_merge($playersInLineup, $lineup->getAllPlayerIds());
        }

        return array_unique($playersInLineup);
    }

    /**
     * Check if specific players are available for a fixture (in lineup and not already matched)
     */
    public function checkPlayersAvailability(array $playerIds, int $fixtureId): array
    {
        $fixture = Fixture::findOrFail($fixtureId);

        // Ensure we have lineup data
        $this->ensureLineupData($fixture);

        $playersInLineup = $this->getPlayersInFixtureLineup($fixture->id);
        $unavailableReasons = [];

        foreach ($playerIds as $playerId) {
            $reasons = [];
            $player = Player::find($playerId);

            if (!$player) {
                $reasons[] = 'Player not found';
            } elseif (!$player->status) {
                $reasons[] = 'Player is inactive';
            } else {
                // Check if player is in the lineup using external_id
                if (!in_array($player->external_id, $playersInLineup)) {
                    $reasons[] = 'Player is not in the fixture lineup';
                }

                // Check if player already has a match for this fixture
                $existingMatch = PlayerMatch::where('player_id', $playerId)
                    ->where('fixture_id', $fixture->id)
                    ->first();

                if ($existingMatch) {
                    $reasons[] = 'Player already has a match created for this fixture';
                }

                // Check if player is in other ongoing matches
                $ongoingMatch = PlayerMatch::where('player_id', $playerId)
                    ->whereHas('fixture', function ($query) use ($fixture) {
                        $query->whereIn('status', [
                            'First Half',
                            'Second Half',
                            'Halftime',
                            'Extra Time',
                            'Penalty In Progress',
                            'Match Suspended',
                            'Match Interrupted'
                        ])
                            ->where('id', '!=', $fixture->id); // Exclude current fixture
                    })
                    ->first();

                if ($ongoingMatch) {
                    $reasons[] = 'Player is currently in another ongoing match';
                }
            }

            if (!empty($reasons)) {
                $unavailableReasons[$playerId] = $reasons;
            }
        }

        return $unavailableReasons;
    }

    /**
     * Get detailed availability status for a specific player
     */
    public function getPlayerAvailabilityStatus(int $playerId): array
    {
        $player = Player::with('team')->find($playerId);

        if (!$player) {
            return [
                'available' => false,
                'status' => 'not_found',
                'reason' => 'Player not found'
            ];
        }

        if (!$player->status) {
            return [
                'available' => false,
                'status' => 'inactive',
                'reason' => 'Player is inactive'
            ];
        }

        // Check for ongoing matches
        $ongoingMatch = PlayerMatch::where('player_id', $playerId)
            ->whereHas('fixture', function ($query) {
                $query->whereIn('status', [
                    'First Half',
                    'Second Half',
                    'Halftime',
                    'Extra Time',
                    'Penalty In Progress',
                    'Match Suspended',
                    'Match Interrupted'
                ]);
            })
            ->with(['fixture', 'team'])
            ->first();

        if ($ongoingMatch) {
            return [
                'available' => false,
                'status' => 'in_match',
                'reason' => 'Currently playing',
                'current_match' => [
                    'fixture_id' => $ongoingMatch->fixture_id,
                    'fixture_status' => $ongoingMatch->fixture->status,
                    'opponent_team' => $ongoingMatch->team->name,
                    'match_date' => $ongoingMatch->date
                ]
            ];
        }

        // Check for upcoming lineups where player is selected
        $upcomingLineups = FixtureLineup::whereHas('fixture', function ($query) {
            $query->whereIn('status', ['Not Started', 'First Half', 'Second Half', 'Halftime']);
        })
            ->get()
            ->filter(function ($lineup) use ($player) {
                return $lineup->hasPlayer($player->external_id);
            });

        if ($upcomingLineups->isNotEmpty()) {
            $lineup = $upcomingLineups->first();
            $isStarting = $lineup->isPlayerStarting($player->external_id);

            return [
                'available' => true,
                'status' => 'selected_in_lineup',
                'reason' => $isStarting ? 'Selected in starting XI' : 'Selected as substitute',
                'lineup_info' => [
                    'fixture_id' => $lineup->fixture_id,
                    'team_name' => $lineup->team_name,
                    'formation' => $lineup->formation,
                    'position' => $lineup->getPlayerPosition($player->external_id),
                    'is_starting' => $isStarting
                ]
            ];
        }

        return [
            'available' => true,
            'status' => 'available',
            'reason' => 'Player is available for selection'
        ];
    }

    /**
     * Get summary of player availability statistics
     */
    public function getAvailabilitySummary(): array
    {
        $totalPlayers = Player::where('status', true)->count();

        // Players currently in ongoing matches
        $playersInOngoingMatches = PlayerMatch::whereHas('fixture', function ($query) {
            $query->whereIn('status', [
                'First Half',
                'Second Half',
                'Halftime',
                'Extra Time',
                'Penalty In Progress',
                'Match Suspended',
                'Match Interrupted'
            ]);
        })
            ->distinct('player_id')
            ->count();

        // Players selected in upcoming lineups
        $playersInUpcomingLineups = FixtureLineup::whereHas('fixture', function ($query) {
            $query->whereIn('status', ['Not Started']);
        })
            ->get()
            ->reduce(function ($carry, $lineup) {
                return array_merge($carry, $lineup->getAllPlayerIds());
            }, []);

        $uniquePlayersInUpcomingLineups = count(array_unique($playersInUpcomingLineups));

        return [
            'total_active_players' => $totalPlayers,
            'players_in_ongoing_matches' => $playersInOngoingMatches,
            'players_in_upcoming_lineups' => $uniquePlayersInUpcomingLineups,
            'available_players' => $totalPlayers - $playersInOngoingMatches
        ];
    }

    /**
     * Ensure lineup data exists for a fixture, fetch if missing
     */
    private function ensureLineupData(Fixture $fixture): void
    {
        $hasLineups = FixtureLineup::where('fixture_id', $fixture->id)->exists();

        if (!$hasLineups && $this->shouldFetchLineup($fixture)) {
            // Dispatch job to fetch lineup data
            FetchFixtureLineupsJob::dispatch($fixture->id);
        }
    }

    /**
     * Determine if we should fetch lineup data for a fixture
     */
    private function shouldFetchLineup(Fixture $fixture): bool
    {
        // Only fetch lineups for fixtures that are starting soon, ongoing, or recently finished
        return in_array($fixture->status, [
            'Not Started',
            'First Half',
            'Second Half',
            'Halftime',
            'Extra Time',
            'Penalty In Progress',
            'Match Finished'
        ]) && $fixture->date >= now()->subHours(3) && $fixture->date <= now()->addHours(6);
    }
}
