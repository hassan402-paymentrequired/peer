<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WalletFundingFailedNotification extends Notification
{
    use Queueable;

    protected $transaction;
    protected string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct($transaction, string $reason = 'Payment failed')
    {
        $this->transaction = $transaction;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add SMS channel if user has phone number
        if ($notifiable->phone) {
            $channels[] = \App\Channels\SmsChannel::class;
        }

        return $channels;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->transaction->amount, 2);

        return "Hi {$notifiable->name}, your wallet funding of ₦{$amount} failed. Reason: {$this->reason}. Please try again or contact support.";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wallet_funding_failed',
            'title' => 'Wallet Funding Failed',
            'message' => "Your wallet funding of ₦" . number_format($this->transaction->amount, 2) . " failed",
            'amount' => $this->transaction->amount,
            'reason' => $this->reason,
            'transaction_ref' => $this->transaction->transaction_ref,
        ];
    }
}
