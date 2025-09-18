<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search products with advanced filtering
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            // Validate search parameters
            $validator = Validator::make($request->all(), [
                'q' => 'nullable|string|max:255',
                'category_id' => 'nullable|array',
                'category_id.*' => 'integer|exists:categories,id',
                'category' => 'nullable|string|exists:categories,slug',
                'price_min' => 'nullable|numeric|min:0',
                'price_max' => 'nullable|numeric|min:0',
                'brand' => 'nullable|array',
                'brand.*' => 'string|max:100',
                'seller_id' => 'nullable|array',
                'seller_id.*' => 'integer|exists:sellers,id',
                'in_stock' => 'nullable|boolean',
                'on_sale' => 'nullable|boolean',
                'min_rating' => 'nullable|numeric|min:0|max:5',
                'sort_by' => 'nullable|string|in:relevance,price_low_to_high,price_high_to_low,rating,popularity,newest,oldest,name,discount',
                'sort_order' => 'nullable|string|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1',
                'created_after' => 'nullable|date',
                'created_before' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $params = $validator->validated();

            // Perform search
            $results = $this->searchService->searchProducts($params);

            // Log search if there's a query
            if (!empty($params['q'])) {
                $userId = auth('api')->id();
                $this->searchService->logSearch(
                    $params['q'], 
                    $results->total(), 
                    $userId
                );
            }

            // Transform results for API response
            $transformedResults = $results->through(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'brand' => $product->brand,
                    'model' => $product->model,
                    'barcode' => $product->barcode,
                    'images' => $product->images,
                    'average_rating' => $product->average_rating,
                    'review_count' => $product->review_count,
                    'view_count' => $product->view_count,
                    'status' => $product->status,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ] : null,
                    'seller' => $product->seller ? [
                        'id' => $product->seller->id,
                        'name' => $product->seller->name,
                        'company_name' => $product->seller->company_name,
                        'logo' => $product->seller->logo,
                        'rating' => $product->seller->rating,
                    ] : null,
                    'current_price' => $product->currentPrice ? [
                        'id' => $product->currentPrice->id,
                        'price' => $product->currentPrice->price,
                        'original_price' => $product->currentPrice->original_price,
                        'discount_percentage' => $product->currentPrice->discount_percentage,
                        'stock_quantity' => $product->currentPrice->stock_quantity,
                        'is_in_stock' => $product->currentPrice->stock_quantity > 0,
                        'currency' => $product->currentPrice->currency,
                        'updated_at' => $product->currentPrice->updated_at,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedResults->items(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                    'has_more_pages' => $results->hasMorePages(),
                ],
                'search_params' => $params,
                'message' => $results->total() > 0 
                    ? "{$results->total()} ürün bulundu" 
                    : 'Arama kriterlerinize uygun ürün bulunamadı'
            ]);

        } catch (\Exception $e) {
            \Log::error('Search error: ' . $e->getMessage(), [
                'params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Arama sırasında bir hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get search suggestions
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'required|string|min:2|max:100',
                'limit' => 'nullable|integer|min:1|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = $request->input('q');
            $limit = $request->input('limit', 10);

            $suggestions = $this->searchService->getSearchSuggestions($query, $limit);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'query' => $query,
                'count' => count($suggestions)
            ]);

        } catch (\Exception $e) {
            \Log::error('Search suggestions error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Öneriler alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get popular searches
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $limit = $request->input('limit', 10);
            $popularSearches = $this->searchService->getPopularSearches($limit);

            return response()->json([
                'success' => true,
                'data' => $popularSearches,
                'count' => count($popularSearches)
            ]);

        } catch (\Exception $e) {
            \Log::error('Popular searches error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Popüler aramalar alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Quick search for autocomplete
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function quickSearch(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'required|string|min:2|max:100',
                'limit' => 'nullable|integer|min:1|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = $request->input('q');
            $limit = $request->input('limit', 5);

            // Quick search with minimal data
            $results = $this->searchService->searchProducts([
                'q' => $query,
                'per_page' => $limit,
                'page' => 1
            ]);

            $quickResults = $results->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'brand' => $product->brand,
                    'image' => $product->images[0] ?? null,
                    'price' => $product->currentPrice?->price,
                    'currency' => $product->currentPrice?->currency ?? 'TRY',
                    'category_name' => $product->category?->name,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $quickResults,
                'query' => $query,
                'total' => $results->total(),
                'has_more' => $results->total() > $limit
            ]);

        } catch (\Exception $e) {
            \Log::error('Quick search error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hızlı arama sırasında hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
