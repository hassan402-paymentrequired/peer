<?php

namespace App\Events;

use App\Models\Tournament;
use App\Models\TournamentUser;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tournament $tournament;
    public $winners;
    public $totalPrizePool;

    public function __construct(Tournament $tournament, $winners, $totalPrizePool)
    {
        $this->tournament = $tournament;
        $this->winners = $winners;
        $this->totalPrizePool = $totalPrizePool;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Broadcast to all tournament participants
        $channels = [];

        // Get all users in this tournament
        $participantIds = TournamentUser::where('tournament_id', $this->tournament->id)
            ->pluck('user_id')
            ->toArray();

        foreach ($participantIds as $userId) {
            $channels[] = new PrivateChannel("user.{$userId}");
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'tournament' => [
                'id' => $this->tournament->id,
                'name' => $this->tournament->name,
                'status' => $this->tournament->status,
            ],
            'message' => "🏆 Tournament '{$this->tournament->name}' has completed!",
            'total_prize_pool' => $this->totalPrizePool,
            'winner_count' => $this->winners->count(),
            'type' => 'tournament_completed',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'tournament.completed';
    }
}
