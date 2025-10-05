<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrizeWon implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public $amount;
    public $competitionType;
    public $competitionName;

    public function __construct(User $user, $amount, $competitionType, $competitionName)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->competitionType = $competitionType;
        $this->competitionName = $competitionName;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->user->id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'amount' => $this->amount,
            'competition_type' => $this->competitionType,
            'competition_name' => $this->competitionName,
            'message' => "🎉 Congratulations! You won ₦" . number_format($this->amount, 2) . " in {$this->competitionName}!",
            'type' => 'prize_won',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'prize.won';
    }
}
