<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PrizeWonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public float $amount,
        public string $competitionType,
        public string $competitionName,
        public float $newBalance
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
        $title = "🎉 Prize Won!";
        $body = "Congratulations! You won ₦" . number_format($this->amount, 2) . " in {$this->competitionName}! Your new balance is ₦" . number_format($this->newBalance, 2);

        return (new WebPushMessage)
            ->title($title)
            ->icon('/images/prize-icon.png')
            ->body($body)
            ->action('View Wallet', 'view_wallet')
            ->data([
                'type' => 'prize_won',
                'amount' => $this->amount,
                'competition_type' => $this->competitionType,
                'competition_name' => $this->competitionName,
                'new_balance' => $this->newBalance,
                'url' => '/wallet'
            ])
            ->options(['TTL' => 86400]); // 24 hours
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'amount' => $this->amount,
            'competition_type' => $this->competitionType,
            'competition_name' => $this->competitionName,
            'new_balance' => $this->newBalance,
        ];
    }
}
