<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\PriceHistory;
use App\Models\User;
use App\Jobs\SendPriceAlertJob;
use App\Jobs\UpdatePriceHistoryJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PriceService
{
    /**
     * Update product price for a seller
     */
    public function updatePrice(int $productId, int $sellerId, float $price, int $stock = null): ProductPrice
    {
        return DB::transaction(function () use ($productId, $sellerId, $price, $stock) {
            $productPrice = ProductPrice::where('product_id', $productId)
                ->where('seller_id', $sellerId)
                ->first();

            $oldPrice = $productPrice ? $productPrice->price : null;
            $oldStock = $productPrice ? $productPrice->stock : null;

            if ($productPrice) {
                // Update existing price
                $productPrice->update([
                    'price' => $price,
                    'stock' => $stock ?? $productPrice->stock,
                    'updated_at' => now()
                ]);
            } else {
                // Create new price entry
                $productPrice = ProductPrice::create([
                    'product_id' => $productId,
                    'seller_id' => $sellerId,
                    'price' => $price,
                    'stock' => $stock ?? 0,
                    'is_active' => true
                ]);
            }

            // Record price history if price changed
            if ($oldPrice !== null && $oldPrice != $price) {
                $this->recordPriceHistory($productPrice, $oldPrice, $price, $oldStock, $stock);
            }

            // Trigger price alerts
            if ($oldPrice !== null && $oldPrice != $price) {
                SendPriceAlertJob::dispatch($productPrice, $oldPrice, $price);
            }

            // Clear cache
            $this->clearPriceCache($productId);

            return $productPrice;
        });
    }

    /**
     * Bulk update prices for a seller
     */
    public function bulkUpdatePrices(int $sellerId, array $priceUpdates): array
    {
        $results = [];
        $errors = [];

        DB::transaction(function () use ($sellerId, $priceUpdates, &$results, &$errors) {
            foreach ($priceUpdates as $update) {
                try {
                    $productId = $update['product_id'];
                    $price = $update['price'];
                    $stock = $update['stock'] ?? null;

                    $productPrice = $this->updatePrice($productId, $sellerId, $price, $stock);
                    $results[] = $productPrice;
                } catch (\Exception $e) {
                    $errors[] = [
                        'product_id' => $update['product_id'] ?? null,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return [
            'success' => $results,
            'errors' => $errors,
            'total_processed' => count($priceUpdates),
            'success_count' => count($results),
            'error_count' => count($errors)
        ];
    }

    /**
     * Record price history
     */
    private function recordPriceHistory(ProductPrice $productPrice, ?float $oldPrice, float $newPrice, ?int $oldStock, ?int $newStock): void
    {
        $changeType = $this->determineChangeType($oldPrice, $newPrice, $oldStock, $newStock);

        PriceHistory::create([
            'product_price_id' => $productPrice->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'old_stock' => $oldStock,
            'new_stock' => $newStock ?? $productPrice->stock,
            'change_type' => $changeType
        ]);
    }

    /**
     * Determine the type of change
     */
    private function determineChangeType(?float $oldPrice, float $newPrice, ?int $oldStock, ?int $newStock): string
    {
        $priceChanged = $oldPrice !== null && $oldPrice != $newPrice;
        $stockChanged = $oldStock !== null && $newStock !== null && $oldStock != $newStock;

        if ($priceChanged && $stockChanged) {
            return 'both';
        } elseif ($priceChanged) {
            return $newPrice > $oldPrice ? 'price_increase' : 'price_decrease';
        } elseif ($stockChanged) {
            return 'stock_change';
        }

        return 'no_change';
    }

    /**
     * Get price history for a product
     */
    public function getPriceHistory(int $productId, int $days = 30): array
    {
        $cacheKey = "price_history_{$productId}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($productId, $days) {
            $startDate = Carbon::now()->subDays($days);
            
            $history = PriceHistory::whereHas('productPrice', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('created_at', '>=', $startDate)
            ->with(['productPrice.seller'])
            ->orderBy('created_at', 'desc')
            ->get();

            return $history->map(function ($item) {
                return [
                    'id' => $item->id,
                    'seller_name' => $item->productPrice->seller->name ?? 'Unknown',
                    'old_price' => $item->old_price,
                    'new_price' => $item->new_price,
                    'old_stock' => $item->old_stock,
                    'new_stock' => $item->new_stock,
                    'change_type' => $item->change_type,
                    'date' => $item->created_at->format('Y-m-d H:i:s'),
                    'formatted_date' => $item->created_at->diffForHumans()
                ];
            })->toArray();
        });
    }

    /**
     * Get price comparison data
     */
    public function getPriceComparison(int $productId): array
    {
        $cacheKey = "price_comparison_{$productId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($productId) {
            $prices = ProductPrice::where('product_id', $productId)
                ->active()
                ->with(['seller'])
                ->orderBy('price', 'asc')
                ->get();

            $stats = $this->calculatePriceStats($prices);
            
            return [
                'prices' => $prices->map(function ($price) {
                    return [
                        'id' => $price->id,
                        'seller_id' => $price->seller_id,
                        'seller_name' => $price->seller->name ?? 'Unknown',
                        'price' => $price->price,
                        'formatted_price' => $price->formatted_price,
                        'stock' => $price->stock,
                        'is_in_stock' => $price->is_in_stock,
                        'updated_at' => $price->updated_at->format('Y-m-d H:i:s')
                    ];
                })->toArray(),
                'stats' => $stats
            ];
        });
    }

    /**
     * Calculate price statistics
     */
    private function calculatePriceStats($prices): array
    {
        if ($prices->isEmpty()) {
            return [
                'min_price' => 0,
                'max_price' => 0,
                'avg_price' => 0,
                'price_range' => 0,
                'seller_count' => 0,
                'in_stock_count' => 0
            ];
        }

        $priceValues = $prices->pluck('price');
        $inStockCount = $prices->where('stock', '>', 0)->count();

        return [
            'min_price' => $priceValues->min(),
            'max_price' => $priceValues->max(),
            'avg_price' => round($priceValues->avg(), 2),
            'price_range' => $priceValues->max() - $priceValues->min(),
            'seller_count' => $prices->count(),
            'in_stock_count' => $inStockCount,
            'out_of_stock_count' => $prices->count() - $inStockCount
        ];
    }

    /**
     * Get price alerts for users
     */
    public function getPriceAlerts(int $productId, float $targetPrice): array
    {
        $currentPrices = ProductPrice::where('product_id', $productId)
            ->active()
            ->where('price', '<=', $targetPrice)
            ->with(['seller'])
            ->orderBy('price', 'asc')
            ->get();

        return $currentPrices->map(function ($price) {
            return [
                'seller_name' => $price->seller->name ?? 'Unknown',
                'price' => $price->price,
                'stock' => $price->stock,
                'is_in_stock' => $price->is_in_stock
            ];
        })->toArray();
    }

    /**
     * Get trending prices (products with significant price changes)
     */
    public function getTrendingPrices(int $hours = 24, float $changeThreshold = 10.0): array
    {
        $cacheKey = "trending_prices_{$hours}_{$changeThreshold}";
        
        return Cache::remember($cacheKey, 1800, function () use ($hours, $changeThreshold) {
            $startTime = Carbon::now()->subHours($hours);
            
            $trendingPrices = PriceHistory::where('created_at', '>=', $startTime)
                ->whereIn('change_type', ['price_increase', 'price_decrease'])
                ->with(['productPrice.product', 'productPrice.seller'])
                ->get()
                ->filter(function ($history) use ($changeThreshold) {
                    $changePercent = abs(($history->new_price - $history->old_price) / $history->old_price * 100);
                    return $changePercent >= $changeThreshold;
                })
                ->sortByDesc(function ($history) {
                    return abs(($history->new_price - $history->old_price) / $history->old_price * 100);
                })
                ->take(50);

            return $trendingPrices->map(function ($history) {
                $changePercent = ($history->new_price - $history->old_price) / $history->old_price * 100;
                
                return [
                    'product_id' => $history->productPrice->product_id,
                    'product_name' => $history->productPrice->product->name ?? 'Unknown',
                    'seller_name' => $history->productPrice->seller->name ?? 'Unknown',
                    'old_price' => $history->old_price,
                    'new_price' => $history->new_price,
                    'change_amount' => $history->new_price - $history->old_price,
                    'change_percent' => round($changePercent, 2),
                    'change_type' => $history->change_type,
                    'date' => $history->created_at->format('Y-m-d H:i:s')
                ];
            })->values()->toArray();
        });
    }

    /**
     * Get price analytics for admin dashboard
     */
    public function getPriceAnalytics(int $days = 30): array
    {
        $cacheKey = "price_analytics_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days);
            
            $totalPriceChanges = PriceHistory::where('created_at', '>=', $startDate)
                ->whereIn('change_type', ['price_increase', 'price_decrease', 'both'])
                ->count();
                
            $priceIncreases = PriceHistory::where('created_at', '>=', $startDate)
                ->whereIn('change_type', ['price_increase', 'both'])
                ->count();
                
            $priceDecreases = PriceHistory::where('created_at', '>=', $startDate)
                ->whereIn('change_type', ['price_decrease', 'both'])
                ->count();
                
            $avgPriceChange = PriceHistory::where('created_at', '>=', $startDate)
                ->whereIn('change_type', ['price_increase', 'price_decrease', 'both'])
                ->selectRaw('AVG(ABS(new_price - old_price)) as avg_change')
                ->value('avg_change') ?? 0;

            return [
                'total_price_changes' => $totalPriceChanges,
                'price_increases' => $priceIncreases,
                'price_decreases' => $priceDecreases,
                'avg_price_change' => round($avgPriceChange, 2),
                'increase_percentage' => $totalPriceChanges > 0 ? round(($priceIncreases / $totalPriceChanges) * 100, 2) : 0,
                'decrease_percentage' => $totalPriceChanges > 0 ? round(($priceDecreases / $totalPriceChanges) * 100, 2) : 0
            ];
        });
    }

    /**
     * Clear price-related cache
     */
    private function clearPriceCache(int $productId): void
    {
        $patterns = [
            "price_comparison_{$productId}",
            "price_history_{$productId}_*",
            "trending_prices_*",
            "price_analytics_*"
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // For wildcard patterns, we'd need to implement cache tag clearing
                // For now, just clear specific known keys
                Cache::forget(str_replace('*', '30', $pattern));
                Cache::forget(str_replace('*', '7', $pattern));
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Validate price data
     */
    public function validatePriceData(array $data): array
    {
        $errors = [];

        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors['price'] = 'Price must be a positive number';
        }

        if (isset($data['stock']) && (!is_numeric($data['stock']) || $data['stock'] < 0)) {
            $errors['stock'] = 'Stock must be a non-negative number';
        }

        if (isset($data['product_id']) && !Product::find($data['product_id'])) {
            $errors['product_id'] = 'Product not found';
        }

        return $errors;
    }

    /**
     * Get seller's price performance
     */
    public function getSellerPricePerformance(int $sellerId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $priceChanges = PriceHistory::whereHas('productPrice', function ($query) use ($sellerId) {
            $query->where('seller_id', $sellerId);
        })
        ->where('created_at', '>=', $startDate)
        ->get();

        $totalProducts = ProductPrice::where('seller_id', $sellerId)->count();
        $activeProducts = ProductPrice::where('seller_id', $sellerId)->active()->count();
        $inStockProducts = ProductPrice::where('seller_id', $sellerId)->inStock()->count();

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'in_stock_products' => $inStockProducts,
            'out_of_stock_products' => $activeProducts - $inStockProducts,
            'price_changes' => $priceChanges->count(),
            'price_increases' => $priceChanges->whereIn('change_type', ['price_increase', 'both'])->count(),
            'price_decreases' => $priceChanges->whereIn('change_type', ['price_decrease', 'both'])->count(),
            'avg_price' => ProductPrice::where('seller_id', $sellerId)->active()->avg('price') ?? 0
        ];
    }
}
