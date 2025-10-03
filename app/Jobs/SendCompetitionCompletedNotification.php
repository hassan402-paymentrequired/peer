<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Models\Peer;
use App\Models\User;
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
        $tournament = Tournament::with('users')->find($this->competitionId);

        if (!$tournament) {
            Log::error("Tournament {$this->competitionId} not found for notifications");
            return;
        }

        foreach ($tournament->users as $user) {
            // Here you would send actual notifications
            // For now, just log
            Log::info("Tournament completed notification sent to user {$user->id}");
        }
    }

    private function notifyPeerParticipants(): void
    {
        $peer = Peer::with('users')->find($this->competitionId);

        if (!$peer) {
            Log::error("Peer {$this->competitionId} not found for notifications");
            return;
        }

        foreach ($peer->users as $user) {
            // Here you would send actual notifications
            // For now, just log
            Log::info("Peer completed notification sent to user {$user->id}");
        }
    }
}
