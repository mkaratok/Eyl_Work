<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PriceService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PriceController extends Controller
{
    protected PriceService $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * Get price history for a product
     */
    public function getPriceHistory(Request $request, int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $days = $request->get('days', 30);
            
            // Validate days parameter
            if ($days < 1 || $days > 365) {
                return response()->json([
                    'success' => false,
                    'message' => 'Days parameter must be between 1 and 365'
                ], 400);
            }

            $history = $this->priceService->getPriceHistory($productId, $days);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'days' => $days,
                    'history' => $history,
                    'total_records' => count($history)
                ],
                'message' => 'Price history retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get price comparison for a product
     */
    public function getPriceComparison(int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $comparison = $this->priceService->getPriceComparison($productId);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'comparison' => $comparison
                ],
                'message' => 'Price comparison retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price comparison: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trending prices
     */
    public function getTrendingPrices(Request $request): JsonResponse
    {
        try {
            $hours = $request->get('hours', 24);
            $changeThreshold = $request->get('change_threshold', 10.0);
            
            // Validate parameters
            if ($hours < 1 || $hours > 168) { // Max 1 week
                return response()->json([
                    'success' => false,
                    'message' => 'Hours parameter must be between 1 and 168'
                ], 400);
            }

            if ($changeThreshold < 0 || $changeThreshold > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Change threshold must be between 0 and 100'
                ], 400);
            }

            $trending = $this->priceService->getTrendingPrices($hours, $changeThreshold);

            return response()->json([
                'success' => true,
                'data' => [
                    'hours' => $hours,
                    'change_threshold' => $changeThreshold,
                    'trending_prices' => $trending,
                    'total_records' => count($trending)
                ],
                'message' => 'Trending prices retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trending prices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get price alerts for a product
     */
    public function getPriceAlerts(Request $request, int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $targetPrice = $request->get('target_price');
            
            if (!$targetPrice || !is_numeric($targetPrice) || $targetPrice <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Valid target price is required'
                ], 400);
            }

            $alerts = $this->priceService->getPriceAlerts($productId, $targetPrice);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'target_price' => $targetPrice,
                    'alerts' => $alerts,
                    'alert_count' => count($alerts)
                ],
                'message' => 'Price alerts retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get price chart data for visualization
     */
    public function getPriceChartData(Request $request, int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $days = $request->get('days', 30);
            $groupBy = $request->get('group_by', 'day'); // day, week, month
            
            // Validate parameters
            if ($days < 1 || $days > 365) {
                return response()->json([
                    'success' => false,
                    'message' => 'Days parameter must be between 1 and 365'
                ], 400);
            }

            if (!in_array($groupBy, ['day', 'week', 'month'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group by must be one of: day, week, month'
                ], 400);
            }

            $history = $this->priceService->getPriceHistory($productId, $days);
            $chartData = $this->formatChartData($history, $groupBy);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'days' => $days,
                    'group_by' => $groupBy,
                    'chart_data' => $chartData
                ],
                'message' => 'Price chart data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price chart data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get price statistics for a product
     */
    public function getPriceStatistics(Request $request, int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $days = $request->get('days', 30);
            
            $comparison = $this->priceService->getPriceComparison($productId);
            $history = $this->priceService->getPriceHistory($productId, $days);
            
            // Calculate additional statistics
            $priceChanges = collect($history)->where('change_type', '!=', 'stock_change');
            $avgChangeAmount = $priceChanges->avg(function ($item) {
                return abs($item['new_price'] - $item['old_price']);
            }) ?? 0;

            $statistics = [
                'current_stats' => $comparison['stats'],
                'history_stats' => [
                    'total_changes' => $priceChanges->count(),
                    'avg_change_amount' => round($avgChangeAmount, 2),
                    'biggest_increase' => $priceChanges->where('change_type', 'price_increase')
                        ->max(function ($item) {
                            return $item['new_price'] - $item['old_price'];
                        }) ?? 0,
                    'biggest_decrease' => $priceChanges->where('change_type', 'price_decrease')
                        ->max(function ($item) {
                            return $item['old_price'] - $item['new_price'];
                        }) ?? 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'days' => $days,
                    'statistics' => $statistics
                ],
                'message' => 'Price statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format price history data for chart visualization
     */
    private function formatChartData(array $history, string $groupBy): array
    {
        $grouped = collect($history)->groupBy(function ($item) use ($groupBy) {
            $date = \Carbon\Carbon::parse($item['date']);
            
            switch ($groupBy) {
                case 'week':
                    return $date->startOfWeek()->format('Y-m-d');
                case 'month':
                    return $date->startOfMonth()->format('Y-m-d');
                default: // day
                    return $date->format('Y-m-d');
            }
        });

        $chartData = [
            'labels' => [],
            'datasets' => []
        ];

        // Get unique sellers
        $sellers = collect($history)->pluck('seller_name')->unique()->values();
        
        // Create dataset for each seller
        foreach ($sellers as $seller) {
            $chartData['datasets'][] = [
                'label' => $seller,
                'data' => [],
                'borderColor' => $this->generateColor($seller),
                'backgroundColor' => $this->generateColor($seller, 0.1),
                'fill' => false
            ];
        }

        // Fill data points
        foreach ($grouped as $date => $items) {
            $chartData['labels'][] = $date;
            
            foreach ($chartData['datasets'] as &$dataset) {
                $sellerData = $items->where('seller_name', $dataset['label'])->first();
                $dataset['data'][] = $sellerData ? $sellerData['new_price'] : null;
            }
        }

        return $chartData;
    }

    /**
     * Generate consistent color for seller
     */
    private function generateColor(string $seller, float $alpha = 1): string
    {
        $hash = md5($seller);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));
        
        if ($alpha < 1) {
            return "rgba($r, $g, $b, $alpha)";
        }
        
        return "rgb($r, $g, $b)";
    }
}
