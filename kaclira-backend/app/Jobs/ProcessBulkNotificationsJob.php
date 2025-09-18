<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class ProcessBulkNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userIds;
    protected $notification;
    protected $batchSize = 50;

    /**
     * Create a new job instance.
     */
    public function __construct(array $userIds, $notification)
    {
        $this->userIds = $userIds;
        $this->notification = $notification;
        $this->onQueue('bulk-notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $totalUsers = count($this->userIds);
            $processedUsers = 0;
            $failedUsers = 0;

            Log::info('Starting bulk notification processing', [
                'total_users' => $totalUsers,
                'notification_type' => get_class($this->notification)
            ]);

            // Process users in batches to avoid memory issues
            $batches = array_chunk($this->userIds, $this->batchSize);

            foreach ($batches as $batchIndex => $userIdsBatch) {
                try {
                    // Get users for this batch
                    $users = User::whereIn('id', $userIdsBatch)->get();

                    // Filter users based on notification preferences
                    $eligibleUsers = $users->filter(function ($user) {
                        return $this->shouldSendNotification($user);
                    });

                    if ($eligibleUsers->isNotEmpty()) {
                        // Send notifications to eligible users
                        Notification::send($eligibleUsers, $this->notification);
                        $processedUsers += $eligibleUsers->count();
                    }

                    $skippedUsers = $users->count() - $eligibleUsers->count();
                    if ($skippedUsers > 0) {
                        Log::info('Skipped users in batch due to preferences', [
                            'batch' => $batchIndex + 1,
                            'skipped_count' => $skippedUsers
                        ]);
                    }

                    // Small delay between batches to prevent overwhelming the system
                    if ($batchIndex < count($batches) - 1) {
                        sleep(1);
                    }

                } catch (\Exception $e) {
                    $failedUsers += count($userIdsBatch);
                    Log::error('Failed to process notification batch', [
                        'batch' => $batchIndex + 1,
                        'user_ids' => $userIdsBatch,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Bulk notification processing completed', [
                'total_users' => $totalUsers,
                'processed_users' => $processedUsers,
                'failed_users' => $failedUsers,
                'notification_type' => get_class($this->notification)
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk notification job failed', [
                'total_users' => count($this->userIds),
                'notification_type' => get_class($this->notification),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Check if notification should be sent to user
     */
    protected function shouldSendNotification(User $user): bool
    {
        $preferences = $user->notification_preferences ?? [];
        $notificationType = get_class($this->notification);

        // Check if user has opted out of all notifications
        if (!($preferences['receive_notifications'] ?? true)) {
            return false;
        }

        // Check specific notification type preferences
        switch ($notificationType) {
            case 'App\Notifications\PriceDropNotification':
                return ($preferences['email_price_alerts'] ?? true) || 
                       ($preferences['push_price_alerts'] ?? true);
                
            case 'App\Notifications\StockAvailableNotification':
                return ($preferences['email_stock_alerts'] ?? true) || 
                       ($preferences['push_stock_alerts'] ?? true);
                
            case 'App\Notifications\CampaignNotification':
                return ($preferences['email_campaigns'] ?? true) || 
                       ($preferences['push_campaigns'] ?? false) ||
                       ($preferences['receive_campaigns'] ?? true);
                
            case 'App\Notifications\PriceAlertNotification':
                return ($preferences['email_price_alerts'] ?? true) || 
                       ($preferences['push_price_alerts'] ?? true);
                
            default:
                return true;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk notification job failed permanently', [
            'total_users' => count($this->userIds),
            'notification_type' => get_class($this->notification),
            'error' => $exception->getMessage()
        ]);
    }
}
