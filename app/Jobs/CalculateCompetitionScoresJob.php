<?php

namespace App\Jobs;

use App\Enum\TransactionStatusEnum;
use App\Models\Tournament;
use App\Models\TournamentUser;
use App\Models\Peer;
use App\Models\PeerUser;
use App\Models\PlayerStatistic;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CalculateCompetitionScoresJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public string $competitionType,
        public int $competitionId
    ) {}

    public function handle(): void
    {
        Log::info("CalculateCompetitionScoresJob started for {$this->competitionType} {$this->competitionId}");

        try {
            if ($this->competitionType === 'tournament') {
                $this->calculateTournamentScores();
            } elseif ($this->competitionType === 'peer') {
                $this->calculatePeerScores();
            }
        } catch (\Exception $e) {
            Log::error("CalculateCompetitionScoresJob failed: " . $e->getMessage(), [
                'competition_type' => $this->competitionType,
                'competition_id' => $this->competitionId,
                'exception' => $e
            ]);
        }

        Log::info("CalculateCompetitionScoresJob completed for {$this->competitionType} {$this->competitionId}");
    }

    private function calculateTournamentScores(): void
    {
        $tournament = Tournament::findOrFail($this->competitionId);

        if ($tournament->status !== 'open') {
            Log::info("Tournament {$this->competitionId} is not open, skipping");
            return;
        }

        DB::beginTransaction();

        try {
            $participants = TournamentUser::with(['squads.mainPlayer', 'squads.subPlayer', 'user'])
                ->where('tournament_id', $tournament->id)
                ->get();

            Log::info("Calculating scores for {$participants->count()} tournament participants");

            foreach ($participants as $participant) {
                $totalPoints = $this->calculateParticipantScore($participant->squads);

                $participant->update(['total_points' => $totalPoints]);

                Log::info("Updated participant {$participant->user_id} with {$totalPoints} points");
            }

            // Determine winners
            $winners = $this->determineTournamentWinners($participants);

            // Update tournament status and mark as calculated
            $tournament->update([
                'status' => 'close',
                'scoring_calculated' => true,
                'scoring_calculated_at' => now()
            ]);

            $totalPrizePool = $this->distributeTournamentPrizes($tournament, $winners);

            // Broadcast tournament completion event
            event(new \App\Events\TournamentCompleted($tournament, $winners, $totalPrizePool));

            // Create notifications for all participants
            app(NotificationService::class)->notifyTournamentCompletion($tournament, $winners, $totalPrizePool);

            DB::commit();

            Log::info("Tournament {$this->competitionId} scoring completed successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculatePeerScores(): void
    {
        $peer = Peer::findOrFail($this->competitionId);

        if ($peer->status !== 'open') {
            Log::info("Peer {$this->competitionId} is not open, skipping");
            return;
        }

        DB::beginTransaction();

        try {
            $participants = PeerUser::with(['squads.mainPlayer', 'squads.subPlayer', 'user'])
                ->where('peer_id', $peer->id)
                ->get();

            Log::info("Calculating scores for {$participants->count()} peer participants");

            foreach ($participants as $participant) {
                $totalPoints = $this->calculateParticipantScore($participant->squads);

                $participant->update(['total_points' => $totalPoints]);

                Log::info("Updated participant {$participant->user_id} with {$totalPoints} points");
            }

            // Determine winner
            $winner = $this->determinePeerWinner($participants);

            // Update peer status and mark as calculated
            $peer->update([
                'status' => 'finished',
                'winner_user_id' => $winner->user_id,
                'scoring_calculated' => true,
                'scoring_calculated_at' => now()
            ]);

            $totalPrizePool = $this->distributePeerPrizes($peer, $winner, $participants);

            // Broadcast peer completion event
            event(new \App\Events\PeerCompleted($peer, $winner, $totalPrizePool));

            // Create notifications for all participants
            app(NotificationService::class)->notifyPeerCompletion($peer, $winner, $totalPrizePool);

            DB::commit();

            Log::info("Peer {$this->competitionId} scoring completed successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateParticipantScore($squads): int
    {
        $totalPoints = 0;

        foreach ($squads as $squad) {
            // Calculate main player points
            $mainPlayerPoints = $this->getPlayerPoints($squad->main_player_id, $squad->main_player_match_id);

            // Check if main player played (has points > 0 or played the match)
            $mainPlayerPlayed = $this->didPlayerPlay($squad->main_player_id, $squad->main_player_match_id);

            if ($mainPlayerPlayed) {
                // Use main player points
                $squadPoints = $mainPlayerPoints;
                $usedPlayer = 'main';
            } else {
                // Use sub player points since main didn't play
                $subPlayerPoints = $this->getPlayerPoints($squad->sub_player_id, $squad->sub_player_match_id);
                $squadPoints = $subPlayerPoints;
                $usedPlayer = 'sub';
            }

            $totalPoints += $squadPoints;

            Log::debug("Squad points calculated", [
                'main_player_id' => $squad->main_player_id,
                'main_player_points' => $mainPlayerPoints,
                'sub_player_id' => $squad->sub_player_id,
                'sub_player_points' => $usedPlayer === 'sub' ? $squadPoints : 0,
                'used_player' => $usedPlayer,
                'squad_total' => $squadPoints
            ]);
        }

        return $totalPoints;
    }

    private function getPlayerPoints(int $playerId, ?int $playerMatchId): int
    {
        if (!$playerMatchId) {
            return 0;
        }

        // Get the fixture_id from player_match
        $playerMatch = \App\Models\PlayerMatch::find($playerMatchId);
        if (!$playerMatch || !$playerMatch->fixture_id) {
            return 0;
        }

        // Get player statistics for this fixture
        $statistic = PlayerStatistic::where('player_id', $playerId)
            ->where('fixture_id', $playerMatch->fixture_id)
            ->first();

        if (!$statistic) {
            Log::warning("No statistics found for player {$playerId} in fixture {$playerMatch->fixture_id}");
            return 0;
        }

        // Use the model's getPointsAttribute method
        return $statistic->points ?? 0;
    }

    private function didPlayerPlay(int $playerId, ?int $playerMatchId): bool
    {
        if (!$playerMatchId) {
            return false;
        }

        // Get the fixture_id from player_match
        $playerMatch = \App\Models\PlayerMatch::find($playerMatchId);
        if (!$playerMatch || !$playerMatch->fixture_id) {
            return false;
        }

        // Get player statistics for this fixture
        $statistic = PlayerStatistic::where('player_id', $playerId)
            ->where('fixture_id', $playerMatch->fixture_id)
            ->first();

        if (!$statistic) {
            return false;
        }

        // Player played if they have did_play = true and are not injured
        return $statistic->did_play && !$statistic->is_injured;
    }

    private function determineTournamentWinners($participants)
    {
        // Sort participants by total points (descending)
        $sortedParticipants = $participants->sortByDesc('total_points');

        if ($sortedParticipants->isEmpty()) {
            Log::warning("No participants found for tournament");
            return collect();
        }

        // Get top 3 winners (handle ties by including all tied participants)
        $winners = collect();
        $currentPosition = 1;
        $previousScore = null;
        $participantsProcessed = 0;

        foreach ($sortedParticipants as $participant) {
            // If score is different from previous, update position
            if ($previousScore !== null && $participant->total_points < $previousScore) {
                $currentPosition = $participantsProcessed + 1;
            }

            // Only include top 3 positions (this may include ties)
            if ($currentPosition <= 3) {
                $participant->update(['is_winner' => true]);

                // Store the position for prize distribution
                $participant->position = $currentPosition;

                $winners->push($participant);
            } else {
                // Stop processing after position 3
                break;
            }

            $previousScore = $participant->total_points;
            $participantsProcessed++;
        }

        Log::info("Tournament winners determined", [
            'total_participants' => $sortedParticipants->count(),
            'winners_count' => $winners->count(),
            'top_3_scores' => $sortedParticipants->take(3)->pluck('total_points')->toArray(),
            'positions_breakdown' => $winners->groupBy('position')->map->count()->toArray()
        ]);

        return $winners;
    }

    private function determinePeerWinner($participants)
    { 
        // Sort participants by total points (descending)
        $sortedParticipants = $participants->sortByDesc('total_points');

        $winner = $sortedParticipants->first();
        $winner->update(['is_winner' => true]);

        Log::info("Peer winner determined", [
            'winner_user_id' => $winner->user_id,
            'winning_score' => $winner->total_points
        ]);

        return $winner;
    }

    private function distributeTournamentPrizes(Tournament $tournament, $winners): float
    {
        if ($winners->isEmpty()) {
            Log::warning("No winners found for tournament {$tournament->id}");
            return 0;
        }

        $totalPrizePool = $tournament->amount * $tournament->users()->count();

        // Deduct system fee (e.g., 10% for the platform)
        $systemFeePercentage = config('tournament.system_fee_percentage', 10); // 10% default
        $systemFee = $totalPrizePool * ($systemFeePercentage / 100);
        $netPrizePool = $totalPrizePool - $systemFee;

        // Prize distribution percentages for top 3
        $prizeDistribution = [
            1 => 50, // 1st place gets 50%
            2 => 30, // 2nd place gets 30%
            3 => 20, // 3rd place gets 20%
        ];

        foreach ($winners as $index => $winner) {
            $position = $index + 1; // Position starts from 1

            // Calculate prize based on position
            if (isset($prizeDistribution[$position])) {
                $prizePercentage = $prizeDistribution[$position];
                $prizeAmount = $netPrizePool * ($prizePercentage / 100);
            } else {
                // If there are more than 3 winners, remaining winners get no prize
                // Or you could split remaining amount equally
                $prizeAmount = 0;
            }

            if ($prizeAmount > 0) {
                // Add to user's wallet
                $winner->user->addBalance($prizeAmount);

                // Store prize amount for notifications
                $winner->prize_amount = $prizeAmount;

                // Create transaction record
                Transaction::create([
                    'user_id' => $winner->user_id,
                    'amount' => $prizeAmount,
                    'action_type' => 'credit',
                    'description' => "Tournament prize (Position {$position}) - {$tournament->name}",
                    'status' => TransactionStatusEnum::SUCCESSFUL->value,
                    'transaction_ref' => 'TournamentPrize' . $winner->user_id . $tournament->id . time() . rand(100000, 999999),
                ]);

                // Send prize won notification
                app(NotificationService::class)->notifyPrizeWon(
                    $winner->user,
                    $prizeAmount,
                    'tournament',
                    $tournament->name,
                    [
                        'tournament_id' => $tournament->id,
                        'position' => $position,
                        'percentage' => $prizePercentage
                    ]
                );

                Log::info("Prize distributed to tournament winner", [
                    'user_id' => $winner->user_id,
                    'position' => $position,
                    'amount' => $prizeAmount,
                    'percentage' => $prizePercentage,
                ]);
            }
        }

        // Log system fee collection
        Log::info("System fee collected from tournament", [
            'tournament_id' => $tournament->id,
            'total_prize_pool' => $totalPrizePool,
            'system_fee' => $systemFee,
            'net_prize_pool' => $netPrizePool,
            'fee_percentage' => $systemFeePercentage,
            'prize_distribution' => '50/30/20'
        ]);

        return $totalPrizePool;
    }


    private function distributePeerPrizes(Peer $peer, $winner, $participants): float
    {
        $totalPrizePool = $peer->amount * $participants->count();

        // Deduct system fee (e.g., 5% for peer competitions)
        $systemFeePercentage = config('peer.system_fee_percentage', 5); // 5% default for peers
        $systemFee = $totalPrizePool * ($systemFeePercentage / 100);
        $netPrizePool = $totalPrizePool - $systemFee;

        if ($peer->sharing_ratio === 1) {
            // Winner takes all (after system fee)
            $prizeAmount = $netPrizePool;
        } else {
            // Divide among participants (this logic might need adjustment based on your business rules)
            $prizeAmount = $netPrizePool * 0.7; // Winner gets 70% of net pool, for example
        }

        // Add to winner's wallet
        $winner->user->addBalance($prizeAmount);

        // Store prize amount for notifications
        $winner->prize_amount = $prizeAmount;

        // Create transaction record
        Transaction::create([
            'user_id' => $winner->user_id,
            'amount' => $prizeAmount,
            'action_type' => 'credit',
            'description' => "Peer competition prize - {$peer->name}",
            'status' => TransactionStatusEnum::SUCCESSFUL->value,
            'transaction_ref' => 'PeerPrize' .  $winner->user_id . time() . rand(100000, 999999)
        ]);

        // Send prize won notification
        app(NotificationService::class)->notifyPrizeWon(
            $winner->user,
            $prizeAmount,
            'peer',
            $peer->name,
            ['peer_id' => $peer->id]
        );

        Log::info("Prize distributed to peer winner", [
            'user_id' => $winner->user_id,
            'amount' => $prizeAmount,
            'system_fee_deducted' => $systemFee,
            'sharing_ratio' => $peer->sharing_ratio
        ]);

        // Log system fee collection
        Log::info("System fee collected from peer", [
            'peer_id' => $peer->id,
            'total_prize_pool' => $totalPrizePool,
            'system_fee' => $systemFee,
            'net_prize_pool' => $netPrizePool,
            'fee_percentage' => $systemFeePercentage
        ]);

        return $totalPrizePool;
    }
}
