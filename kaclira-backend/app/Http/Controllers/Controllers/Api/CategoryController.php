<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Get hierarchical category tree
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $activeOnly = $request->boolean('active_only', true);
            $useCache = $request->boolean('use_cache', true);
            
            $categories = $this->categoryService->getCategoryTree($activeOnly, $useCache);
            
            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories,
                'meta' => [
                    'total' => $categories->count(),
                    'active_only' => $activeOnly,
                    'cached' => $useCache
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get flat category list
     */
    public function flat(Request $request): JsonResponse
    {
        try {
            $activeOnly = $request->boolean('active_only', true);
            $useCache = $request->boolean('use_cache', true);
            
            $categories = $this->categoryService->getFlatCategories($activeOnly, $useCache);
            
            return response()->json([
                'success' => true,
                'message' => 'Flat categories retrieved successfully',
                'data' => $categories,
                'meta' => [
                    'total' => $categories->count(),
                    'active_only' => $activeOnly,
                    'cached' => $useCache
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve flat categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search categories
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100'
        ]);

        try {
            $term = $request->input('q');
            $activeOnly = $request->boolean('active_only', true);
            
            $categories = $this->categoryService->searchCategories($term, $activeOnly);
            
            return response()->json([
                'success' => true,
                'message' => 'Category search completed',
                'data' => $categories,
                'meta' => [
                    'query' => $term,
                    'total' => $categories->count(),
                    'active_only' => $activeOnly
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single category with details
     */
    public function show(Request $request, $identifier): JsonResponse
    {
        try {
            // Find by ID or slug
            $category = is_numeric($identifier) 
                ? Category::with(['parent', 'children', 'products'])->findOrFail($identifier)
                : Category::with(['parent', 'children', 'products'])->where('slug', $identifier)->firstOrFail();

            $includeProducts = $request->boolean('include_products', false);
            $includeAncestors = $request->boolean('include_ancestors', false);
            $includeDescendants = $request->boolean('include_descendants', false);

            $data = [
                'category' => $category,
                'breadcrumb' => $category->breadcrumb,
                'full_name' => $category->full_name,
                'has_children' => $category->hasChildren(),
            ];

            if ($includeProducts) {
                $data['products'] = $category->products()->active()->paginate(20);
            }

            if ($includeAncestors) {
                $data['ancestors'] = $category->getAncestors();
            }

            if ($includeDescendants) {
                $data['descendants'] = $category->getDescendants();
            }

            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get category suggestions for product
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|min:3|max:500',
            'limit' => 'integer|min:1|max:20'
        ]);

        try {
            $text = $request->input('text');
            $limit = $request->input('limit', 5);
            
            $suggestions = $this->categoryService->suggestCategories($text, $limit);
            
            return response()->json([
                'success' => true,
                'message' => 'Category suggestions generated',
                'data' => $suggestions,
                'meta' => [
                    'text' => $text,
                    'limit' => $limit,
                    'total' => $suggestions->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured categories
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            
            $categories = Category::active()
                ->featured()
                ->with(['children' => function ($query) {
                    $query->active()->limit(5);
                }])
                ->orderBy('sort_order')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Featured categories retrieved',
                'data' => $categories,
                'meta' => [
                    'total' => $categories->count(),
                    'limit' => $limit
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories by level
     */
    public function byLevel(Request $request, int $level): JsonResponse
    {
        try {
            $activeOnly = $request->boolean('active_only', true);
            
            $query = Category::byLevel($level)->with('parent');
            
            if ($activeOnly) {
                $query->active();
            }
            
            $categories = $query->orderBy('sort_order')->get();
            
            return response()->json([
                'success' => true,
                'message' => "Level {$level} categories retrieved",
                'data' => $categories,
                'meta' => [
                    'level' => $level,
                    'total' => $categories->count(),
                    'active_only' => $activeOnly
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories by level',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_categories' => Category::count(),
                'active_categories' => Category::active()->count(),
                'featured_categories' => Category::featured()->count(),
                'root_categories' => Category::rootCategories()->count(),
                'categories_by_level' => [],
                'categories_with_products' => Category::has('products')->count(),
            ];

            // Get categories count by level
            for ($level = 0; $level <= 5; $level++) {
                $count = Category::byLevel($level)->count();
                if ($count > 0) {
                    $stats['categories_by_level'][$level] = $count;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Category statistics retrieved',
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
