<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FilterController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Get all filter options for search
     * 
     * @return JsonResponse
     */
    public function options(): JsonResponse
    {
        try {
            $filterOptions = $this->searchService->getFilterOptions();

            return response()->json([
                'success' => true,
                'data' => $filterOptions,
                'message' => 'Filter options retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Filter options error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Filter seçenekleri alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get category filter options
     * 
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        try {
            $filterOptions = $this->searchService->getFilterOptions();

            return response()->json([
                'success' => true,
                'data' => $filterOptions['categories'],
                'message' => 'Category options retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Category filter options error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kategori seçenekleri alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get brand filter options
     * 
     * @return JsonResponse
     */
    public function brands(): JsonResponse
    {
        try {
            $filterOptions = $this->searchService->getFilterOptions();

            return response()->json([
                'success' => true,
                'data' => $filterOptions['brands'],
                'message' => 'Brand options retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Brand filter options error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Marka seçenekleri alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get seller filter options
     * 
     * @return JsonResponse
     */
    public function sellers(): JsonResponse
    {
        try {
            $filterOptions = $this->searchService->getFilterOptions();

            return response()->json([
                'success' => true,
                'data' => $filterOptions['sellers'],
                'message' => 'Seller options retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Seller filter options error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Satıcı seçenekleri alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get price range for filtering
     * 
     * @return JsonResponse
     */
    public function priceRange(): JsonResponse
    {
        try {
            $filterOptions = $this->searchService->getFilterOptions();

            return response()->json([
                'success' => true,
                'data' => $filterOptions['price_range'],
                'message' => 'Price range retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Price range error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Fiyat aralığı alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get sort options
     * 
     * @return JsonResponse
     */
    public function sortOptions(): JsonResponse
    {
        try {
            $filterOptions = $this->searchService->getFilterOptions();

            return response()->json([
                'success' => true,
                'data' => $filterOptions['sort_options'],
                'message' => 'Sort options retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Sort options error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Sıralama seçenekleri alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get dynamic filters based on current search
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function dynamicFilters(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'nullable|string|max:255',
                'category_id' => 'nullable|array',
                'category_id.*' => 'integer|exists:categories,id',
                'category' => 'nullable|string|exists:categories,slug',
                'brand' => 'nullable|array',
                'brand.*' => 'string|max:100',
                'seller_id' => 'nullable|array',
                'seller_id.*' => 'integer|exists:sellers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $params = $validator->validated();

            // Get base filter options
            $baseOptions = $this->searchService->getFilterOptions();

            // TODO: Implement dynamic filtering based on current search context
            // This would filter the available options based on what's actually available
            // in the current search results
            
            return response()->json([
                'success' => true,
                'data' => $baseOptions,
                'context' => $params,
                'message' => 'Dynamic filters retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Dynamic filters error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Dinamik filtreler alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
