<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PhoneVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $verificationCode;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "Your " . config('app.name') . " verification code is: {$this->verificationCode}. This code expires in 10 minutes.";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'verification_code' => $this->verificationCode,
            'phone' => $notifiable->phone,
        ];
    }
}
