<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Get products with enhanced V2 features
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'seller_id' => 'nullable|exists:sellers,id',
            'search' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:name,price,rating,created_at,popularity',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include' => 'nullable|string', // Enhanced: comma-separated relations
            'fields' => 'nullable|string', // Enhanced: field selection
        ]);

        $products = $this->productService->getProducts($validated);

        // V2 Enhancement: Include additional metadata
        $meta = [
            'total_categories' => $products->total() > 0 ? Product::distinct('category_id')->count() : 0,
            'price_range' => [
                'min' => Product::min('price'),
                'max' => Product::max('price'),
            ],
            'filters_applied' => array_filter([
                'category' => $validated['category_id'] ?? null,
                'seller' => $validated['seller_id'] ?? null,
                'search' => $validated['search'] ?? null,
                'price_range' => isset($validated['min_price']) || isset($validated['max_price']),
            ]),
        ];

        return ApiResponse::paginated($products, 'Products retrieved successfully')
            ->header('X-API-Enhancement', 'v2-metadata');
    }

    /**
     * Get single product with enhanced details
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'include' => 'nullable|string',
            'with_analytics' => 'nullable|boolean', // V2 Enhancement
        ]);

        $product = $this->productService->getProductBySlug($slug, $validated);

        if (!$product) {
            return ApiResponse::notFound('Product not found');
        }

        // V2 Enhancement: Include analytics data
        if ($validated['with_analytics'] ?? false) {
            $product->analytics = [
                'view_count' => $product->view_count ?? 0,
                'favorite_count' => $product->favorites_count ?? 0,
                'price_alerts_count' => $product->price_alerts_count ?? 0,
                'last_price_update' => $product->prices()->latest()->first()?->updated_at,
            ];
        }

        return ApiResponse::resource($product, 'Product retrieved successfully');
    }

    /**
     * Get product price history with enhanced analytics
     */
    public function priceHistory(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'group_by' => 'nullable|in:day,week,month', // V2 Enhancement
            'include_predictions' => 'nullable|boolean', // V2 Enhancement
        ]);

        $product = Product::findOrFail($id);
        $priceHistory = $this->productService->getPriceHistory($product, $validated);

        // V2 Enhancement: Add price analytics
        $analytics = [
            'average_price' => $priceHistory->avg('price'),
            'lowest_price' => $priceHistory->min('price'),
            'highest_price' => $priceHistory->max('price'),
            'price_volatility' => $this->calculatePriceVolatility($priceHistory),
            'trend' => $this->calculatePriceTrend($priceHistory),
        ];

        return ApiResponse::success([
            'product' => $product,
            'price_history' => $priceHistory,
            'analytics' => $analytics,
        ], 'Price history retrieved successfully');
    }

    /**
     * Calculate price volatility
     */
    private function calculatePriceVolatility($priceHistory): float
    {
        if ($priceHistory->count() < 2) {
            return 0;
        }

        $prices = $priceHistory->pluck('price')->toArray();
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(fn($price) => pow($price - $mean, 2), $prices)) / count($prices);
        
        return sqrt($variance);
    }

    /**
     * Calculate price trend
     */
    private function calculatePriceTrend($priceHistory): string
    {
        if ($priceHistory->count() < 2) {
            return 'stable';
        }

        $first = $priceHistory->first()->price;
        $last = $priceHistory->last()->price;
        $change = (($last - $first) / $first) * 100;

        if ($change > 5) return 'increasing';
        if ($change < -5) return 'decreasing';
        return 'stable';
    }
}
