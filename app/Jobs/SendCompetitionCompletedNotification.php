<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Models\Peer;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendCompetitionCompletedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $competitionType,
        public int $competitionId
    ) {}

    public function handle(): void
    {
        try {
            if ($this->competitionType === 'tournament') {
                $this->notifyTournamentParticipants();
            } elseif ($this->competitionType === 'peer') {
                $this->notifyPeerParticipants();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send competition completed notifications: ' . $e->getMessage());
        }
    }

    private function notifyTournamentParticipants(): void
    {
        $tournament = Tournament::with(['users.user'])->find($this->competitionId);

        if (!$tournament) {
            Log::error("Tournament {$this->competitionId} not found for notifications");
            return;
        }

        $winners = $tournament->users()->where('is_winner', true)->with('user')->get();
        $totalPrizePool = $tournament->amount * $tournament->users()->count();

        app(NotificationService::class)->notifyTournamentCompletion($tournament, $winners, $totalPrizePool);

        Log::info("Tournament completed notifications sent for tournament {$this->competitionId}");
    }

    private function notifyPeerParticipants(): void
    {
        $peer = Peer::with(['users.user'])->find($this->competitionId);

        if (!$peer) {
            Log::error("Peer {$this->competitionId} not found for notifications");
            return;
        }

        $winner = $peer->users()->where('is_winner', true)->with('user')->first();
        $totalPrizePool = $peer->amount * $peer->users()->count();

        if ($winner) {
            app(NotificationService::class)->notifyPeerCompletion($peer, $winner, $totalPrizePool);
        }

        Log::info("Peer completed notifications sent for peer {$this->competitionId}");
    }
}
