<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get products list with filters and pagination
     * GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'category_id',
                'brand',
                'min_price',
                'max_price',
                'in_stock',
                'featured',
                'sort_by',
                'sort_direction'
            ]);

            // Only show published and approved products for public API
            $filters['status'] = 'published';
            $filters['admin_approved'] = true;

            $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page
            $products = $this->productService->searchProducts($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single product details
     * GET /api/products/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $product = Product::with([
                'category',
                'activePrices.seller',
                'priceHistory' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                }
            ])
            ->published()
            ->approved()
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product by slug
     * GET /api/products/slug/{slug}
     */
    public function showBySlug($slug): JsonResponse
    {
        try {
            $product = Product::with([
                'category',
                'activePrices.seller',
                'priceHistory' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                }
            ])
            ->where('slug', $slug)
            ->published()
            ->approved()
            ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $product
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products
     * GET /api/products/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => ['total' => 0]
                ]);
            }

            $filters = [
                'search' => $query,
                'status' => 'published',
                'admin_approved' => true
            ];

            // Additional filters
            if ($request->has('category_id')) {
                $filters['category_id'] = $request->get('category_id');
            }

            if ($request->has('brand')) {
                $filters['brand'] = $request->get('brand');
            }

            $perPage = min($request->get('limit', 10), 20); // Max 20 for search
            $products = $this->productService->searchProducts($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'meta' => [
                    'total' => $products->total(),
                    'query' => $query
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product suggestions for autocomplete
     * GET /api/products/suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $products = Product::select(['id', 'name', 'brand', 'slug', 'thumbnail'])
                ->published()
                ->approved()
                ->search($query)
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured products
     * GET /api/products/featured
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = min($request->get('limit', 12), 50);
            
            $products = Product::with(['category', 'activePrices'])
                ->published()
                ->approved()
                ->featured()
                ->orderBy('sort_order')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category
     * GET /api/products/category/{categoryId}
     */
    public function byCategory($categoryId, Request $request): JsonResponse
    {
        try {
            $filters = [
                'category_id' => $categoryId,
                'status' => 'published',
                'admin_approved' => true
            ];

            // Additional filters
            if ($request->has('brand')) {
                $filters['brand'] = $request->get('brand');
            }

            if ($request->has('min_price') || $request->has('max_price')) {
                $filters['min_price'] = $request->get('min_price');
                $filters['max_price'] = $request->get('max_price');
            }

            if ($request->has('sort_by')) {
                $filters['sort_by'] = $request->get('sort_by');
                $filters['sort_direction'] = $request->get('sort_direction', 'desc');
            }

            $perPage = min($request->get('per_page', 15), 50);
            $products = $this->productService->searchProducts($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get related products
     * GET /api/products/{id}/related
     */
    public function related($id, Request $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $limit = min($request->get('limit', 8), 20);

            $related = Product::with(['category', 'activePrices'])
                ->published()
                ->approved()
                ->where('id', '!=', $id)
                ->where(function ($query) use ($product) {
                    $query->where('category_id', $product->category_id)
                          ->orWhere('brand', $product->brand);
                })
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $related
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch related products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product brands
     * GET /api/products/brands
     */
    public function brands(): JsonResponse
    {
        try {
            $brands = Product::select('brand')
                ->published()
                ->approved()
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->groupBy('brand')
                ->orderBy('brand')
                ->pluck('brand');

            return response()->json([
                'success' => true,
                'data' => $brands->values()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch brands',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product statistics
     * GET /api/products/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_products' => Product::published()->approved()->count(),
                'total_brands' => Product::published()->approved()->whereNotNull('brand')->distinct('brand')->count(),
                'featured_products' => Product::published()->approved()->featured()->count(),
                'in_stock_products' => Product::published()->approved()->inStock()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
