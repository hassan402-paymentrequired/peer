<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PeerCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $peerName,
        public bool $isWinner,
        public int $totalPoints,
        public string $winnerName,
        public float $prizeAmount = 0
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the WebPush representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $title = $this->isWinner ?
            "🎯 Peer Champion!" :
            "Peer Competition Completed";

        $body = $this->isWinner ?
            "Amazing! You won '{$this->peerName}' with {$this->totalPoints} points and earned ₦" . number_format($this->prizeAmount, 2) :
            "'{$this->peerName}' has ended. You scored {$this->totalPoints} points. Winner: {$this->winnerName}";

        return (new WebPushMessage)
            ->title($title)
            ->icon('/images/peer-icon.png')
            ->body($body)
            ->action('View Results', 'view_peer_results')
            ->data([
                'type' => 'peer_completed',
                'peer_name' => $this->peerName,
                'is_winner' => $this->isWinner,
                'total_points' => $this->totalPoints,
                'winner_name' => $this->winnerName,
                'prize_amount' => $this->prizeAmount,
                'url' => '/peers/results'
            ])
            ->options(['TTL' => 86400]); // 24 hours
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'peer_name' => $this->peerName,
            'is_winner' => $this->isWinner,
            'total_points' => $this->totalPoints,
            'winner_name' => $this->winnerName,
            'prize_amount' => $this->prizeAmount,
        ];
    }
}
