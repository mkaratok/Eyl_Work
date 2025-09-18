<?php

namespace App\Jobs;

use App\Models\ProductPrice;
use App\Models\PriceHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdatePriceHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ProductPrice $productPrice;
    protected ?float $oldPrice;
    protected float $newPrice;
    protected ?int $oldStock;
    protected ?int $newStock;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ProductPrice $productPrice,
        ?float $oldPrice,
        float $newPrice,
        ?int $oldStock = null,
        ?int $newStock = null
    ) {
        $this->productPrice = $productPrice;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
        $this->oldStock = $oldStock;
        $this->newStock = $newStock;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Determine change type
            $changeType = $this->determineChangeType();

            // Create price history record
            PriceHistory::create([
                'product_price_id' => $this->productPrice->id,
                'old_price' => $this->oldPrice,
                'new_price' => $this->newPrice,
                'old_stock' => $this->oldStock,
                'new_stock' => $this->newStock ?? $this->productPrice->stock,
                'change_type' => $changeType
            ]);

            Log::info('Price history updated', [
                'product_price_id' => $this->productPrice->id,
                'product_id' => $this->productPrice->product_id,
                'seller_id' => $this->productPrice->seller_id,
                'old_price' => $this->oldPrice,
                'new_price' => $this->newPrice,
                'change_type' => $changeType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update price history', [
                'product_price_id' => $this->productPrice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Determine the type of change
     */
    private function determineChangeType(): string
    {
        $priceChanged = $this->oldPrice !== null && $this->oldPrice != $this->newPrice;
        $stockChanged = $this->oldStock !== null && $this->newStock !== null && $this->oldStock != $this->newStock;

        if ($priceChanged && $stockChanged) {
            return 'both';
        } elseif ($priceChanged) {
            return $this->newPrice > $this->oldPrice ? 'price_increase' : 'price_decrease';
        } elseif ($stockChanged) {
            return 'stock_change';
        }

        return 'no_change';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdatePriceHistoryJob failed', [
            'product_price_id' => $this->productPrice->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
