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

            // Calculate sub player points
            $subPlayerPoints = $this->getPlayerPoints($squad->sub_player_id, $squad->sub_player_match_id);

            $squadPoints = $mainPlayerPoints + $subPlayerPoints;
            $totalPoints += $squadPoints;

            Log::debug("Squad points calculated", [
                'main_player_id' => $squad->main_player_id,
                'main_player_points' => $mainPlayerPoints,
                'sub_player_id' => $squad->sub_player_id,
                'sub_player_points' => $subPlayerPoints,
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

    private function determineTournamentWinners($participants)
    {
        // Sort participants by total points (descending)
        $sortedParticipants = $participants->sortByDesc('total_points');

        // Get top 3 winners (or fewer if there are ties)
        $winners = collect();
        $currentPosition = 1;
        $previousScore = null;
        $participantsProcessed = 0;

        foreach ($sortedParticipants as $participant) {
            // If score is different from previous, update position
            if ($previousScore !== null && $participant->total_points < $previousScore) {
                $currentPosition = $participantsProcessed + 1;
            }

            // Only include top 3 positions
            if ($currentPosition <= 3) {
                $participant->update(['is_winner' => true]);
                $winners->push($participant);
            }

            $previousScore = $participant->total_points;
            $participantsProcessed++;
        }

        Log::info("Tournament winners determined", [
            'total_participants' => $sortedParticipants->count(),
            'winners_count' => $winners->count(),
            'top_3_scores' => $sortedParticipants->take(3)->pluck('total_points')->toArray()
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

        $prizePerWinner = $netPrizePool / $winners->count();

        foreach ($winners as $winner) {
            // Add to user's wallet
            $winner->user->addBalance($prizePerWinner);

            // Store prize amount for notifications
            $winner->prize_amount = $prizePerWinner;

            // Create transaction record
            Transaction::create([
                'user_id' => $winner->user_id,
                'amount' => $prizePerWinner,
                'action_type' => 'credit',
                'description' => "Tournament prize - {$tournament->name}",
                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                'transaction_ref' => 'TournamentPrize' . $winner->user_id . $tournament->name . $prizePerWinner
            ]);

            // Send prize won notification
            app(NotificationService::class)->notifyPrizeWon(
                $winner->user,
                $prizePerWinner,
                'tournament',
                $tournament->name,
                ['tournament_id' => $tournament->id]
            );

            Log::info("Prize distributed to tournament winner", [
                'user_id' => $winner->user_id,
                'amount' => $prizePerWinner,
                'system_fee_deducted' => $systemFee / $winners->count()
            ]);
        }

        // Log system fee collection
        Log::info("System fee collected from tournament", [
            'tournament_id' => $tournament->id,
            'total_prize_pool' => $totalPrizePool,
            'system_fee' => $systemFee,
            'net_prize_pool' => $netPrizePool,
            'fee_percentage' => $systemFeePercentage,
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
            'transaction_ref' => 'PeerPrize' .  $winner->user_id . $prizeAmount
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
