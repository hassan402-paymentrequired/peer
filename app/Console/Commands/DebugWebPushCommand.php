<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TournamentCompletedNotification;
use Illuminate\Console\Command;
use NotificationChannels\WebPush\PushSubscription;

class DebugWebPushCommand extends Command
{
    protected $signature = 'debug:webpush {userId}';
    protected $description = 'Debug WebPush sending for a user';

    public function handle()
    {
        $userId = $this->argument('userId');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User {$userId} not found");
            return;
        }

        $this->info("Checking subscriptions for user {$user->name}...");
        $count = $user->pushSubscriptions()->count();
        $this->info("Found {$count} subscriptions.");

        if ($count === 0) {
            $this->error("User has no subscriptions. Cannot test.");
            return;
        }

        $this->info("Sending test notification...");
        
        try {
            $user->notify(new TournamentCompletedNotification(
                'Debug Tournament',
                true,
                100,
                5000
            ));
            $this->info("Notification dispatched.");
        } catch (\Throwable $e) {
            $this->error("Error sending notification: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
