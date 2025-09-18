<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $notification;
    protected $retryCount = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if user still wants email notifications
            if (!$this->shouldSendEmail()) {
                Log::info('Email notification skipped due to user preferences', [
                    'user_id' => $this->user->id,
                    'notification_type' => get_class($this->notification)
                ]);
                return;
            }

            // Send the notification via email
            $this->user->notify($this->notification);

            Log::info('Email notification sent successfully', [
                'user_id' => $this->user->id,
                'notification_type' => get_class($this->notification),
                'email' => $this->user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $this->user->id,
                'notification_type' => get_class($this->notification),
                'error' => $e->getMessage()
            ]);

            // Retry the job if it hasn't exceeded retry limit
            if ($this->attempts() < $this->retryCount) {
                $this->release(300); // Retry after 5 minutes
            }
        }
    }

    /**
     * Check if email should be sent based on user preferences
     */
    protected function shouldSendEmail(): bool
    {
        $preferences = $this->user->notification_preferences ?? [];
        
        // Check global email preference
        if (!($preferences['email_notifications'] ?? true)) {
            return false;
        }

        // Check specific notification type preferences
        $notificationType = get_class($this->notification);
        
        switch ($notificationType) {
            case 'App\Notifications\PriceDropNotification':
                return $preferences['email_price_alerts'] ?? true;
                
            case 'App\Notifications\StockAvailableNotification':
                return $preferences['email_stock_alerts'] ?? true;
                
            case 'App\Notifications\CampaignNotification':
                return $preferences['email_campaigns'] ?? true;
                
            case 'App\Notifications\PriceAlertNotification':
                return $preferences['email_price_alerts'] ?? true;
                
            default:
                return true;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email notification job failed permanently', [
            'user_id' => $this->user->id,
            'notification_type' => get_class($this->notification),
            'error' => $exception->getMessage()
        ]);
    }
}
