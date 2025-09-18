<?php

namespace App\Jobs;

use App\Models\ProductPrice;
use App\Models\User;
use App\Models\UserFavorite;
use App\Notifications\PriceAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendPriceAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ProductPrice $productPrice;
    protected ?float $oldPrice;
    protected float $newPrice;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductPrice $productPrice, ?float $oldPrice, float $newPrice)
    {
        $this->productPrice = $productPrice;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Only send alerts for price decreases (good news for users)
            if ($this->oldPrice === null || $this->newPrice >= $this->oldPrice) {
                return;
            }

            $priceDecrease = $this->oldPrice - $this->newPrice;
            $decreasePercentage = ($priceDecrease / $this->oldPrice) * 100;

            // Only send alerts for significant price drops (>= 5%)
            if ($decreasePercentage < 5) {
                return;
            }

            // Get users who have this product in favorites
            $users = User::whereHas('favorites', function ($query) {
                $query->where('product_id', $this->productPrice->product_id);
            })->get();

            // Send notifications to users
            foreach ($users as $user) {
                try {
                    $user->notify(new PriceAlertNotification(
                        $this->productPrice,
                        $this->oldPrice,
                        $this->newPrice,
                        $priceDecrease,
                        $decreasePercentage
                    ));

                    Log::info('Price alert sent to user', [
                        'user_id' => $user->id,
                        'product_id' => $this->productPrice->product_id,
                        'seller_id' => $this->productPrice->seller_id,
                        'old_price' => $this->oldPrice,
                        'new_price' => $this->newPrice,
                        'decrease_percentage' => round($decreasePercentage, 2)
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to send price alert to user', [
                        'user_id' => $user->id,
                        'product_id' => $this->productPrice->product_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Also send to admin for monitoring
            $this->notifyAdmins($decreasePercentage);

        } catch (\Exception $e) {
            Log::error('Failed to process price alerts', [
                'product_price_id' => $this->productPrice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Notify admins about significant price changes
     */
    private function notifyAdmins(float $decreasePercentage): void
    {
        // Only notify admins for very significant drops (>= 20%)
        if ($decreasePercentage < 20) {
            return;
        }

        try {
            $admins = User::role('admin')->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new PriceAlertNotification(
                    $this->productPrice,
                    $this->oldPrice,
                    $this->newPrice,
                    $this->oldPrice - $this->newPrice,
                    $decreasePercentage,
                    true // isAdminAlert
                ));
            }

        } catch (\Exception $e) {
            Log::error('Failed to send price alert to admins', [
                'product_price_id' => $this->productPrice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPriceAlertJob failed', [
            'product_price_id' => $this->productPrice->id,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
