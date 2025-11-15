<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    protected $notifiable;
    protected Notification $notification;

    /**
     * Create a new job instance.
     */
    public function __construct($notifiable, Notification $notification)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->notifiable->notify($this->notification);

            Log::info('Notification sent successfully', [
                'notifiable_type' => get_class($this->notifiable),
                'notifiable_id' => $this->notifiable->id ?? null,
                'notification_type' => get_class($this->notification),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'notifiable_type' => get_class($this->notifiable),
                'notifiable_id' => $this->notifiable->id ?? null,
                'notification_type' => get_class($this->notification),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
