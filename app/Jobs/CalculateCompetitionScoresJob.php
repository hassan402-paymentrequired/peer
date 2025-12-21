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
    public int $timeout = 300;

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
        // CRITICAL FIX #1: Use lockForUpdate to prevent race conditions
        $tournament = Tournament::lockForUpdate()->findOrFail($this->competitionId);

        // CRITICAL FIX #2: Check if already calculated BEFORE doing anything
        if ($tournament->scoring_calculated) {
            Log::info("Tournament {$this->competitionId} already calculated, skipping to prevent duplicates");
            return;
        }

        if ($tournament->status !== 'open') {
            Log::info("Tournament {$this->competitionId} is not open, skipping");
            return;
        }

        DB::beginTransaction();

        try {
            // CRITICAL FIX #3: Set flag IMMEDIATELY to prevent duplicate jobs
            $tournament->update([
                'scoring_calculated' => true,
                'scoring_calculated_at' => now()
            ]);

            $participants = TournamentUser::with(['squads.mainPlayer', 'squads.subPlayer', 'user'])
                ->where('tournament_id', $tournament->id)
                ->get();

            Log::info("Calculating scores for {$participants->count()} tournament participants");

            // Issue #1: Handle single participant - refund entry fee
            if ($participants->count() === 1) {
                $participant = $participants->first();
                $refundAmount = $tournament->amount;

                // CRITICAL FIX #4: Use deterministic transaction reference
                $transactionRef = 'TournamentRefund_' . $tournament->id . '_' . $participant->user_id;

                // CRITICAL FIX #5: Check for existing transaction
                $existingTransaction = Transaction::where('transaction_ref', $transactionRef)->first();
                if (!$existingTransaction) {
                    // Refund the entry fee
                    $participant->user->addBalance($refundAmount);

                    // Create transaction record
                    Transaction::create([
                        'user_id' => $participant->user_id,
                        'amount' => $refundAmount,
                        'action_type' => 'credit',
                        'description' => "Tournament entry fee refund (insufficient participants) - {$tournament->name}",
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                        'transaction_ref' => $transactionRef,
                    ]);

                    Log::info("Tournament {$this->competitionId} had only one participant. Entry fee refunded.", [
                        'user_id' => $participant->user_id,
                        'refund_amount' => $refundAmount
                    ]);
                } else {
                    Log::info("Refund already processed for tournament {$this->competitionId}");
                }

                // Update tournament status
                $tournament->update(['status' => 'close']);

                DB::commit();
                return;
            }

            foreach ($participants as $participant) {
                $totalPoints = $this->calculateParticipantScore($participant->squads);
                $participant->update(['total_points' => $totalPoints]);
                Log::info("Updated participant {$participant->user_id} with {$totalPoints} points");
            }

            $winners = $this->determineTournamentWinners($participants);

            $tournament->update(['status' => 'close']);

            $totalPrizePool = $this->distributeTournamentPrizes($tournament, $winners);

            // Broadcast tournament completion event
            event(new \App\Events\TournamentCompleted($tournament, $winners, $totalPrizePool));

            // Create notifications for all participants
            app(NotificationService::class)->notifyTournamentCompletion($tournament, $winners, $totalPrizePool);

            DB::commit();

            Log::info("Tournament {$this->competitionId} scoring completed successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            
            // CRITICAL FIX #6: Reset flag on failure so job can be retried
            $tournament->update([
                'scoring_calculated' => false,
                'scoring_calculated_at' => null
            ]);
            
            Log::error("Tournament {$this->competitionId} calculation failed, flag reset for retry", [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function calculatePeerScores(): void
    {
        // CRITICAL FIX #1: Use lockForUpdate to prevent race conditions
        $peer = Peer::lockForUpdate()->findOrFail($this->competitionId);

        // CRITICAL FIX #2: Check if already calculated BEFORE doing anything
        if ($peer->scoring_calculated) {
            Log::info("Peer {$this->competitionId} already calculated, skipping to prevent duplicates");
            return;
        }

        if ($peer->status !== 'open') {
            Log::info("Peer {$this->competitionId} is not open, skipping");
            return;
        }

        DB::beginTransaction();

        try {
            // CRITICAL FIX #3: Set flag IMMEDIATELY to prevent duplicate jobs
            $peer->update([
                'scoring_calculated' => true,
                'scoring_calculated_at' => now()
            ]);

            $participants = PeerUser::with(['squads.mainPlayer', 'squads.subPlayer', 'user'])
                ->where('peer_id', $peer->id)
                ->get();

            Log::info("Calculating scores for {$participants->count()} peer participants");

            // Issue #2: Handle single participant (creator only) - refund entry fee
            if ($participants->count() === 1) {
                $participant = $participants->first();
                $refundAmount = $peer->amount;

                // CRITICAL FIX #4: Use deterministic transaction reference
                $transactionRef = 'PeerRefund_' . $peer->id . '_' . $participant->user_id;

                // CRITICAL FIX #5: Check for existing transaction
                $existingTransaction = Transaction::where('transaction_ref', $transactionRef)->first();
                if (!$existingTransaction) {
                    // Refund the entry fee to the creator
                    $participant->user->addBalance($refundAmount);

                    // Create transaction record
                    Transaction::create([
                        'user_id' => $participant->user_id,
                        'amount' => $refundAmount,
                        'action_type' => 'credit',
                        'description' => "Peer entry fee refund (no opponents) - {$peer->name}",
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                        'transaction_ref' => $transactionRef,
                    ]);

                    Log::info("Peer {$this->competitionId} had only one participant (creator). Entry fee refunded.", [
                        'user_id' => $participant->user_id,
                        'refund_amount' => $refundAmount
                    ]);
                } else {
                    Log::info("Refund already processed for peer {$this->competitionId}");
                }

                // Update peer status
                $peer->update(['status' => 'finished']);

                DB::commit();
                return;
            }

            foreach ($participants as $participant) {
                $totalPoints = $this->calculateParticipantScore($participant->squads);

                $participant->update(['total_points' => $totalPoints]);

                Log::info("Updated participant {$participant->user_id} with {$totalPoints} points");
            }

            // Determine winner(s) based on sharing_ratio
            $winners = $this->determinePeerWinners($peer, $participants);

            // Update peer status and mark as calculated
            $peer->update([
                'status' => 'finished',
                'winner_user_id' => $winners->first()->user_id,
            ]);

            $totalPrizePool = $this->distributePeerPrizes($peer, $winners, $participants);

            // Broadcast peer completion event
            event(new \App\Events\PeerCompleted($peer, $winners->first(), $totalPrizePool));

            // Create notifications for all participants
            app(NotificationService::class)->notifyPeerCompletion($peer, $winners->first(), $totalPrizePool);

            DB::commit();

            Log::info("Peer {$this->competitionId} scoring completed successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            
            // CRITICAL FIX #6: Reset flag on failure so job can be retried
            $peer->update([
                'scoring_calculated' => false,
                'scoring_calculated_at' => null
            ]);
            
            Log::error("Peer {$this->competitionId} calculation failed, flag reset for retry", [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function calculateParticipantScore($squads): int
    {
        $totalPoints = 0;

        foreach ($squads as $squad) {

            $mainPlayerPlayed = $this->didPlayerPlay($squad->main_player_id, $squad->main_player_match_id);

            if ($mainPlayerPlayed) {
                $mainPlayerPoints = $this->getPlayerPoints($squad->main_player_id, $squad->main_player_match_id);
                $squadPoints = $mainPlayerPoints;
                $usedPlayer = 'main';
            } else {
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

        $playerMatch = \App\Models\PlayerMatch::find($playerMatchId);
        if (!$playerMatch || !$playerMatch->fixture_id) {
            return 0;
        }

        $statistic = PlayerStatistic::where('player_id', $playerId)
            ->where('fixture_id', $playerMatch->fixture_id)
            ->first();

        if (!$statistic) {
            Log::warning("No statistics found for player {$playerId} in fixture {$playerMatch->fixture_id}");
            return 0;
        }
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
        if ($participants->isEmpty()) {
            Log::warning("No participants found for tournament");
            return collect();
        }

        // Issue #4: Prevent duplicate winners from same user
        // Group by user_id and select only the highest scoring entry per user
        $bestEntriesPerUser = $participants->groupBy('user_id')->map(function ($userEntries) {
            return $userEntries->sortByDesc('total_points')->first();
        })->values();

        Log::info("Filtered to best entries per user", [
            'original_count' => $participants->count(),
            'filtered_count' => $bestEntriesPerUser->count()
        ]);

        // Sort by total points descending
        $sortedParticipants = $bestEntriesPerUser->sortByDesc('total_points');

        $winners = collect();
        $currentPosition = 1;
        $previousScore = null;
        $participantsProcessed = 0;

        foreach ($sortedParticipants as $participant) {
            if ($previousScore !== null && $participant->total_points < $previousScore) {
                $currentPosition = $participantsProcessed + 1;
            }

            // Only include top 3 positions (this may include ties)
            if ($currentPosition <= 3) {
                $participant->update(['is_winner' => true]);
                $participant->position = $currentPosition;
                $winners->push($participant);
            } else {
                break;
            }
            $previousScore = $participant->total_points;
            $participantsProcessed++;
        }

        return $winners;
    }

    private function determinePeerWinners(Peer $peer, $participants)
    {
        // Sort participants by total points (descending)
        $sortedParticipants = $participants->sortByDesc('total_points');

        // Issue #3: Determine winners based on sharing_ratio
        if ($peer->sharing_ratio === 1) {
            // Winner takes all
            $winner = $sortedParticipants->first();
            $winner->update(['is_winner' => true]);

            Log::info("Peer winner determined (winner takes all)", [
                'winner_user_id' => $winner->user_id,
                'winning_score' => $winner->total_points
            ]);

            return collect([$winner]);
        } else {
            // sharing_ratio = 2: Distribute to top 3 positions
            $winners = collect();
            $currentPosition = 1;
            $previousScore = null;
            $participantsProcessed = 0;

            foreach ($sortedParticipants as $participant) {
                if ($previousScore !== null && $participant->total_points < $previousScore) {
                    $currentPosition = $participantsProcessed + 1;
                }

                // Only include top 3 positions
                if ($currentPosition <= 3) {
                    $participant->update(['is_winner' => true]);
                    $participant->position = $currentPosition;
                    $winners->push($participant);
                } else {
                    break;
                }
                $previousScore = $participant->total_points;
                $participantsProcessed++;
            }

            Log::info("Peer winners determined (top 3 distribution)", [
                'winners_count' => $winners->count(),
                'top_score' => $winners->first()->total_points
            ]);

            return $winners;
        }
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

        $prizeDistribution = [
            1 => 50, // 1st place gets 50%
            2 => 30, // 2nd place gets 30%
            3 => 20, // 3rd place gets 20%
        ];

        foreach ($winners as $index => $winner) {
            $position = $index + 1; 

            // Calculate prize based on position
            if (isset($prizeDistribution[$position])) {
                $prizePercentage = $prizeDistribution[$position];
                $prizeAmount = $netPrizePool * ($prizePercentage / 100);
            } else {
                $prizeAmount = 0;
            }

            if ($prizeAmount > 0) {
                // CRITICAL FIX: Use deterministic transaction reference
                $transactionRef = 'TournamentPrize_' . $tournament->id . '_' . $winner->user_id . '_' . $position;

                // CRITICAL FIX: Check for existing transaction before distributing prize
                $existingTransaction = Transaction::where('transaction_ref', $transactionRef)->first();
                
                if ($existingTransaction) {
                    Log::info("Prize already distributed to tournament winner", [
                        'user_id' => $winner->user_id,
                        'position' => $position,
                        'transaction_ref' => $transactionRef
                    ]);
                    
                    // Store prize amount for notifications (already distributed)
                    $winner->prize_amount = $prizeAmount;
                    continue;
                }

                // Add to user's wallet
                $winner->user->addBalance($prizeAmount);

                // Store prize amount for notifications
                $winner->prize_amount = $prizeAmount;

                Transaction::create([
                    'user_id' => $winner->user_id,
                    'amount' => $prizeAmount,
                    'action_type' => 'credit',
                    'description' => "Tournament prize (Position {$position}) - {$tournament->name}",
                    'status' => TransactionStatusEnum::SUCCESSFUL->value,
                    'transaction_ref' => $transactionRef,
                ]);

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


    private function distributePeerPrizes(Peer $peer, $winners, $participants): float
    {
        $totalPrizePool = $peer->amount * $participants->count();

        // Deduct system fee (e.g., 10% for peer competitions)
        $systemFeePercentage = config('peer.system_fee_percentage', 10);
        $systemFee = $totalPrizePool * ($systemFeePercentage / 100);
        $netPrizePool = $totalPrizePool - $systemFee;

        if ($peer->sharing_ratio === 1) {
            // Winner takes all (after system fee)
            $winner = $winners->first();
            $prizeAmount = $netPrizePool;

            // CRITICAL FIX: Use deterministic transaction reference
            $transactionRef = 'PeerPrize_' . $peer->id . '_' . $winner->user_id . '_WTA';

            // CRITICAL FIX: Check for existing transaction before distributing prize
            $existingTransaction = Transaction::where('transaction_ref', $transactionRef)->first();
            
            if ($existingTransaction) {
                Log::info("Prize already distributed to peer winner (winner takes all)", [
                    'user_id' => $winner->user_id,
                    'transaction_ref' => $transactionRef
                ]);
                
                // Store prize amount for notifications (already distributed)
                $winner->prize_amount = $prizeAmount;
            } else {
                // Add to winner's wallet
                $winner->user->addBalance($prizeAmount);

                // Store prize amount for notifications
                $winner->prize_amount = $prizeAmount;

                // Create transaction record
                Transaction::create([
                    'user_id' => $winner->user_id,
                    'amount' => $prizeAmount,
                    'action_type' => 'credit',
                    'description' => "Peer competition prize (Winner Takes All) - {$peer->name}",
                    'status' => TransactionStatusEnum::SUCCESSFUL->value,
                    'transaction_ref' => $transactionRef
                ]);

                // Send prize won notification
                app(NotificationService::class)->notifyPrizeWon(
                    $winner->user,
                    $prizeAmount,
                    'peer',
                    $peer->name,
                    ['peer_id' => $peer->id]
                );

                Log::info("Prize distributed to peer winner (winner takes all)", [
                    'user_id' => $winner->user_id,
                    'amount' => $prizeAmount,
                    'system_fee_deducted' => $systemFee,
                    'sharing_ratio' => $peer->sharing_ratio
                ]);
            }
        } else {
            // sharing_ratio = 2: Distribute to top 3 (50/30/20 split)
            $prizeDistribution = [
                1 => 50, // 1st place gets 50%
                2 => 30, // 2nd place gets 30%
                3 => 20, // 3rd place gets 20%
            ];

            foreach ($winners as $winner) {
                $position = $winner->position ?? 1;

                // Calculate prize based on position
                if (isset($prizeDistribution[$position])) {
                    $prizePercentage = $prizeDistribution[$position];
                    $prizeAmount = $netPrizePool * ($prizePercentage / 100);
                } else {
                    $prizeAmount = 0;
                }

                if ($prizeAmount > 0) {
                    // CRITICAL FIX: Use deterministic transaction reference
                    $transactionRef = 'PeerPrize_' . $peer->id . '_' . $winner->user_id . '_' . $position;

                    // CRITICAL FIX: Check for existing transaction before distributing prize
                    $existingTransaction = Transaction::where('transaction_ref', $transactionRef)->first();
                    
                    if ($existingTransaction) {
                        Log::info("Prize already distributed to peer winner", [
                            'user_id' => $winner->user_id,
                            'position' => $position,
                            'transaction_ref' => $transactionRef
                        ]);
                        
                        // Store prize amount for notifications (already distributed)
                        $winner->prize_amount = $prizeAmount;
                        continue;
                    }

                    // Add to user's wallet
                    $winner->user->addBalance($prizeAmount);

                    // Store prize amount for notifications
                    $winner->prize_amount = $prizeAmount;

                    Transaction::create([
                        'user_id' => $winner->user_id,
                        'amount' => $prizeAmount,
                        'action_type' => 'credit',
                        'description' => "Peer competition prize (Position {$position}) - {$peer->name}",
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                        'transaction_ref' => $transactionRef,
                    ]);

                    app(NotificationService::class)->notifyPrizeWon(
                        $winner->user,
                        $prizeAmount,
                        'peer',
                        $peer->name,
                        [
                            'peer_id' => $peer->id,
                            'position' => $position,
                            'percentage' => $prizePercentage
                        ]
                    );

                    Log::info("Prize distributed to peer winner", [
                        'user_id' => $winner->user_id,
                        'position' => $position,
                        'amount' => $prizeAmount,
                        'percentage' => $prizePercentage,
                    ]);
                }
            }
        }

        // Log system fee collection
        Log::info("System fee collected from peer", [
            'peer_id' => $peer->id,
            'total_prize_pool' => $totalPrizePool,
            'system_fee' => $systemFee,
            'net_prize_pool' => $netPrizePool,
            'fee_percentage' => $systemFeePercentage,
            'sharing_ratio' => $peer->sharing_ratio
        ]);

        return $totalPrizePool;
    }
}
