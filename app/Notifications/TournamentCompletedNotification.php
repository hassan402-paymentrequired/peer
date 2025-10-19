<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TournamentCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tournamentName,
        public bool $isWinner,
        public int $totalPoints,
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
            "🏆 Tournament Winner!" :
            "Tournament Completed";

        $body = $this->isWinner ?
            "Congratulations! You won '{$this->tournamentName}' with {$this->totalPoints} points and earned ₦" . number_format($this->prizeAmount, 2) :
            "'{$this->tournamentName}' has ended. You scored {$this->totalPoints} points.";

        return (new WebPushMessage)
            ->title($title)
            ->icon('/images/trophy-icon.png')
            ->body($body)
            ->action('View Results', 'view_tournament_results')
            ->data([
                'type' => 'tournament_completed',
                'tournament_name' => $this->tournamentName,
                'is_winner' => $this->isWinner,
                'total_points' => $this->totalPoints,
                'prize_amount' => $this->prizeAmount,
                'url' => '/tournament/results'
            ])
            ->options(['TTL' => 86400]); // 24 hours
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tournament_name' => $this->tournamentName,
            'is_winner' => $this->isWinner,
            'total_points' => $this->totalPoints,
            'prize_amount' => $this->prizeAmount,
        ];
    }
}
