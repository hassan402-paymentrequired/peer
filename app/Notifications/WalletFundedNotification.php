<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WalletFundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Transaction $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
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
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->transaction->amount, 2);
        $balance = number_format($this->transaction->wallet_balance_after, 2);

        return "Hi {$notifiable->name}, your wallet has been funded with ₦{$amount}. New balance: ₦{$balance}. Thank you for using " . config('app.name') . "!";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wallet_funded',
            'title' => 'Wallet Funded Successfully',
            'message' => "Your wallet has been funded with ₦" . number_format($this->transaction->amount, 2),
            'amount' => $this->transaction->amount,
            'new_balance' => $this->transaction->wallet_balance_after,
            'transaction_ref' => $this->transaction->transaction_ref,
        ];
    }
}
