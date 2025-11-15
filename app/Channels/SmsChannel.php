<?php

namespace App\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $phoneNumber = $this->getPhoneNumber($notifiable);

        if (!$phoneNumber) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (is_string($message)) {
            $this->smsService->sendSms($phoneNumber, $message);
        } elseif (is_array($message)) {
            $this->smsService->sendSms(
                $phoneNumber,
                $message['message'] ?? '',
                $message['sender'] ?? null
            );
        }
    }

    /**
     * Get the phone number from the notifiable entity.
     */
    protected function getPhoneNumber(object $notifiable): ?string
    {
        if (isset($notifiable->phone)) {
            return $notifiable->phone;
        }

        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return $notifiable->routeNotificationForSms();
        }

        return null;
    }
}
