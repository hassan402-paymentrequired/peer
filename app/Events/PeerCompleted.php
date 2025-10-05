<?php

namespace App\Events;

use App\Models\Peer;
use App\Models\PeerUser;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PeerCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Peer $peer;
    public PeerUser $winner;
    public $totalPrizePool;

    public function __construct(Peer $peer, PeerUser $winner, $totalPrizePool)
    {
        $this->peer = $peer;
        $this->winner = $winner;
        $this->totalPrizePool = $totalPrizePool;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Broadcast to all peer participants
        $channels = [];

        // Get all users in this peer
        $participantIds = PeerUser::where('peer_id', $this->peer->id)
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
            'peer' => [
                'id' => $this->peer->id,
                'name' => $this->peer->name,
                'status' => $this->peer->status,
            ],
            'winner' => [
                'id' => $this->winner->user_id,
                'username' => $this->winner->user->name ?? 'Unknown',
                'total_points' => $this->winner->total_points,
            ],
            'message' => "🎯 Peer '{$this->peer->name}' has completed!",
            'total_prize_pool' => $this->totalPrizePool,
            'type' => 'peer_completed',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'peer.completed';
    }
}
