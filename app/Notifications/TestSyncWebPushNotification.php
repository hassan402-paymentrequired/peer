<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TestSyncWebPushNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('🧪 Test Notification (Sync)')
            ->icon('/images/logo.png')
            ->body('This is a synchronous test notification from Laravel backend.')
            ->data([
                'type' => 'test',
                'url' => '/dashboard'
            ]);
    }
}
