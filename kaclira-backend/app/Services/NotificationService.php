<?php

namespace App\Services;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Notifications\PriceDropNotification;
use App\Notifications\StockAvailableNotification;
use App\Notifications\CampaignNotification;
use App\Notifications\PriceAlertNotification;
use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\ProcessBulkNotificationsJob;

class NotificationService
{
    /**
     * Send price drop notification to relevant users
     */
    public function sendPriceDropNotification(Product $product, float $oldPrice, float $newPrice): void
    {
        try {
            $dropPercentage = (($oldPrice - $newPrice) / $oldPrice) * 100;
            
            // Only send if drop is significant (>= 5%)
            if ($dropPercentage < 5) {
                return;
            }

            // Get users who have this product in favorites or price alerts
            $users = $this->getUsersForPriceAlert($product, $newPrice);
            
            if ($users->isEmpty()) {
                return;
            }

            $notification = new PriceDropNotification($product, $oldPrice, $newPrice);
            
            // Send notifications
            Notification::send($users, $notification);
            
            Log::info('Price drop notification sent', [
                'product_id' => $product->id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'users_count' => $users->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send price drop notification', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send stock available notification
     */
    public function sendStockAvailableNotification(Product $product, int $stockQuantity): void
    {
        try {
            // Get users who have stock alerts for this product
            $users = $this->getUsersForStockAlert($product);
            
            if ($users->isEmpty()) {
                return;
            }

            $notification = new StockAvailableNotification($product, $stockQuantity);
            
            Notification::send($users, $notification);
            
            // Remove stock alerts since product is now available
            $this->removeStockAlerts($product);
            
            Log::info('Stock available notification sent', [
                'product_id' => $product->id,
                'stock_quantity' => $stockQuantity,
                'users_count' => $users->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send stock available notification', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send campaign notification to targeted users
     */
    public function sendCampaignNotification(array $campaign, array $targetCriteria = []): void
    {
        try {
            $users = $this->getUsersForCampaign($targetCriteria);
            
            if ($users->isEmpty()) {
                Log::warning('No users found for campaign', ['campaign_id' => $campaign['id'] ?? null]);
                return;
            }

            $notification = new CampaignNotification($campaign);
            
            // Use bulk processing for large campaigns
            if ($users->count() > 100) {
                Queue::push(new ProcessBulkNotificationsJob($users->pluck('id')->toArray(), $notification));
            } else {
                Notification::send($users, $notification);
            }
            
            Log::info('Campaign notification sent', [
                'campaign_id' => $campaign['id'] ?? null,
                'users_count' => $users->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send campaign notification', [
                'campaign_id' => $campaign['id'] ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send price alert notification when target price is reached
     */
    public function sendPriceAlertNotification(ProductPrice $productPrice, float $oldPrice, float $newPrice): void
    {
        try {
            $priceDecrease = $oldPrice - $newPrice;
            $decreasePercentage = $oldPrice > 0 ? ($priceDecrease / $oldPrice) * 100 : 0;
            
            // Get users with price alerts for this product at this price level
            $users = $this->getUsersWithPriceAlerts($productPrice->product, $newPrice);
            
            if ($users->isEmpty()) {
                return;
            }

            $notification = new PriceAlertNotification(
                $productPrice,
                $oldPrice,
                $newPrice,
                $priceDecrease,
                $decreasePercentage
            );
            
            Notification::send($users, $notification);
            
            // Mark price alerts as triggered
            $this->markPriceAlertsAsTriggered($productPrice->product, $newPrice);
            
            Log::info('Price alert notification sent', [
                'product_id' => $productPrice->product_id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'users_count' => $users->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send price alert notification', [
                'product_price_id' => $productPrice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send push notification via job
     */
    public function sendPushNotification(User $user, array $data): void
    {
        if (!$this->shouldSendPushNotification($user)) {
            return;
        }

        Queue::push(new SendPushNotificationJob($user, $data));
    }

    /**
     * Send email notification via job
     */
    public function sendEmailNotification(User $user, $notification): void
    {
        if (!$this->shouldSendEmailNotification($user)) {
            return;
        }

        Queue::push(new SendEmailNotificationJob($user, $notification));
    }

    /**
     * Process bulk notifications
     */
    public function processBulkNotifications(array $userIds, $notification): void
    {
        Queue::push(new ProcessBulkNotificationsJob($userIds, $notification));
    }

    /**
     * Get user notification preferences
     */
    public function getUserPreferences(User $user): array
    {
        return $user->notification_preferences ?? $this->getDefaultPreferences();
    }

    /**
     * Update user notification preferences
     */
    public function updateUserPreferences(User $user, array $preferences): bool
    {
        try {
            $user->update([
                'notification_preferences' => array_merge(
                    $this->getDefaultPreferences(),
                    $preferences
                )
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get default notification preferences
     */
    protected function getDefaultPreferences(): array
    {
        return [
            'email_price_alerts' => true,
            'email_stock_alerts' => true,
            'email_campaigns' => true,
            'email_order_updates' => true,
            'push_price_alerts' => true,
            'push_stock_alerts' => true,
            'push_campaigns' => false,
            'push_order_updates' => true,
            'receive_campaigns' => true,
            'frequency' => 'immediate', // immediate, daily, weekly
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00'
        ];
    }

    /**
     * Get users for price alert
     */
    protected function getUsersForPriceAlert(Product $product, float $newPrice): \Illuminate\Database\Eloquent\Collection
    {
        return User::where(function ($query) use ($product, $newPrice) {
            // Users with favorites
            $query->whereHas('favorites', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            // Users with price alerts
            ->orWhereHas('priceAlerts', function ($q) use ($product, $newPrice) {
                $q->where('product_id', $product->id)
                  ->where('target_price', '>=', $newPrice)
                  ->where('is_active', true);
            });
        })
        ->whereJsonContains('notification_preferences->email_price_alerts', true)
        ->orWhereJsonContains('notification_preferences->push_price_alerts', true)
        ->get();
    }

    /**
     * Get users for stock alert
     */
    protected function getUsersForStockAlert(Product $product): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('stockAlerts', function ($query) use ($product) {
            $query->where('product_id', $product->id)
                  ->where('is_active', true);
        })
        ->where(function ($query) {
            $query->whereJsonContains('notification_preferences->email_stock_alerts', true)
                  ->orWhereJsonContains('notification_preferences->push_stock_alerts', true);
        })
        ->get();
    }

    /**
     * Get users for campaign
     */
    protected function getUsersForCampaign(array $criteria): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::query();
        
        // Filter by user segments
        if (isset($criteria['segments'])) {
            $query->whereJsonContains('segments', $criteria['segments']);
        }
        
        // Filter by location
        if (isset($criteria['locations'])) {
            $query->whereIn('location', $criteria['locations']);
        }
        
        // Filter by registration date
        if (isset($criteria['registered_after'])) {
            $query->where('created_at', '>=', $criteria['registered_after']);
        }
        
        // Filter by last activity
        if (isset($criteria['active_since'])) {
            $query->where('last_activity_at', '>=', $criteria['active_since']);
        }
        
        // Only users who opted in for campaigns
        $query->where(function ($q) {
            $q->whereJsonContains('notification_preferences->receive_campaigns', true)
              ->orWhereNull('notification_preferences');
        });
        
        return $query->get();
    }

    /**
     * Get users with price alerts for specific product and price
     */
    protected function getUsersWithPriceAlerts(Product $product, float $newPrice): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('priceAlerts', function ($query) use ($product, $newPrice) {
            $query->where('product_id', $product->id)
                  ->where('target_price', '>=', $newPrice)
                  ->where('is_active', true);
        })->get();
    }

    /**
     * Check if push notification should be sent
     */
    protected function shouldSendPushNotification(User $user): bool
    {
        $preferences = $this->getUserPreferences($user);
        
        // Check if user enabled push notifications
        if (!($preferences['push_notifications'] ?? true)) {
            return false;
        }
        
        // Check quiet hours
        if ($this->isQuietHours($preferences)) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if email notification should be sent
     */
    protected function shouldSendEmailNotification(User $user): bool
    {
        $preferences = $this->getUserPreferences($user);
        
        return $preferences['email_notifications'] ?? true;
    }

    /**
     * Check if current time is within quiet hours
     */
    protected function isQuietHours(array $preferences): bool
    {
        $now = now()->format('H:i');
        $start = $preferences['quiet_hours_start'] ?? '22:00';
        $end = $preferences['quiet_hours_end'] ?? '08:00';
        
        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        } else {
            return $now >= $start || $now <= $end;
        }
    }

    /**
     * Remove stock alerts for product
     */
    protected function removeStockAlerts(Product $product): void
    {
        $product->stockAlerts()->update(['is_active' => false]);
    }

    /**
     * Mark price alerts as triggered
     */
    protected function markPriceAlertsAsTriggered(Product $product, float $price): void
    {
        $product->priceAlerts()
            ->where('target_price', '>=', $price)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'triggered_at' => now()
            ]);
    }
}
