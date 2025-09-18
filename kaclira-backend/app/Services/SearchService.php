<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Perform advanced product search with filters
     */
    public function searchProducts(array $params): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'seller', 'currentPrice'])
            ->where('status', 'approved')
            ->where('is_active', true);

        // Apply search query
        if (!empty($params['q'])) {
            $query = $this->applyTextSearch($query, $params['q']);
        }

        // Apply filters
        $query = $this->applyFilters($query, $params);

        // Apply sorting
        $query = $this->applySorting($query, $params);

        // Get pagination parameters
        $perPage = min((int)($params['per_page'] ?? 20), 50);
        $page = (int)($params['page'] ?? 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply full-text search
     */
    protected function applyTextSearch(Builder $query, string $searchTerm): Builder
    {
        $searchTerm = trim($searchTerm);
        
        if (empty($searchTerm)) {
            return $query;
        }

        // Split search term into words
        $words = explode(' ', $searchTerm);
        $words = array_filter($words, fn($word) => strlen($word) > 2);

        return $query->where(function ($q) use ($words, $searchTerm) {
            // Exact match gets highest priority
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('description', 'LIKE', "%{$searchTerm}%")
              ->orWhere('brand', 'LIKE', "%{$searchTerm}%")
              ->orWhere('model', 'LIKE', "%{$searchTerm}%")
              ->orWhere('barcode', $searchTerm);

            // Individual word matches
            foreach ($words as $word) {
                $q->orWhere('name', 'LIKE', "%{$word}%")
                  ->orWhere('description', 'LIKE', "%{$word}%")
                  ->orWhere('brand', 'LIKE', "%{$word}%")
                  ->orWhere('model', 'LIKE', "%{$word}%");
            }

            // Category name search
            $q->orWhereHas('category', function ($categoryQuery) use ($searchTerm, $words) {
                $categoryQuery->where('name', 'LIKE', "%{$searchTerm}%");
                foreach ($words as $word) {
                    $categoryQuery->orWhere('name', 'LIKE', "%{$word}%");
                }
            });

            // Seller name search
            $q->orWhereHas('seller', function ($sellerQuery) use ($searchTerm, $words) {
                $sellerQuery->where('name', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('company_name', 'LIKE', "%{$searchTerm}%");
                foreach ($words as $word) {
                    $sellerQuery->orWhere('name', 'LIKE', "%{$word}%")
                               ->orWhere('company_name', 'LIKE', "%{$word}%");
                }
            });
        });
    }

    /**
     * Apply various filters
     */
    protected function applyFilters(Builder $query, array $params): Builder
    {
        // Category filter
        if (!empty($params['category_id'])) {
            $categoryIds = is_array($params['category_id']) 
                ? $params['category_id'] 
                : [$params['category_id']];
            
            $query->whereIn('category_id', $categoryIds);
        }

        // Category slug filter
        if (!empty($params['category'])) {
            $query->whereHas('category', function ($q) use ($params) {
                $q->where('slug', $params['category']);
            });
        }

        // Price range filter
        if (!empty($params['price_min']) || !empty($params['price_max'])) {
            $query->whereHas('currentPrice', function ($q) use ($params) {
                if (!empty($params['price_min'])) {
                    $q->where('price', '>=', (float)$params['price_min']);
                }
                if (!empty($params['price_max'])) {
                    $q->where('price', '<=', (float)$params['price_max']);
                }
            });
        }

        // Brand filter
        if (!empty($params['brand'])) {
            $brands = is_array($params['brand']) ? $params['brand'] : [$params['brand']];
            $query->whereIn('brand', $brands);
        }

        // Stock availability filter
        if (isset($params['in_stock']) && $params['in_stock'] !== '') {
            $inStock = filter_var($params['in_stock'], FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('currentPrice', function ($q) use ($inStock) {
                if ($inStock) {
                    $q->where('stock_quantity', '>', 0);
                } else {
                    $q->where('stock_quantity', '<=', 0);
                }
            });
        }

        // Seller filter
        if (!empty($params['seller_id'])) {
            $sellerIds = is_array($params['seller_id']) 
                ? $params['seller_id'] 
                : [$params['seller_id']];
            
            $query->whereIn('seller_id', $sellerIds);
        }

        // Rating filter
        if (!empty($params['min_rating'])) {
            $query->where('average_rating', '>=', (float)$params['min_rating']);
        }

        // Discount filter
        if (isset($params['on_sale']) && $params['on_sale'] !== '') {
            $onSale = filter_var($params['on_sale'], FILTER_VALIDATE_BOOLEAN);
            if ($onSale) {
                $query->whereHas('currentPrice', function ($q) {
                    $q->where('discount_percentage', '>', 0);
                });
            }
        }

        // Date filters
        if (!empty($params['created_after'])) {
            $query->where('created_at', '>=', $params['created_after']);
        }

        if (!empty($params['created_before'])) {
            $query->where('created_at', '<=', $params['created_before']);
        }

        return $query;
    }

    /**
     * Apply sorting
     */
    protected function applySorting(Builder $query, array $params): Builder
    {
        $sortBy = $params['sort_by'] ?? 'relevance';
        $sortOrder = $params['sort_order'] ?? 'desc';

        // Validate sort order
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        switch ($sortBy) {
            case 'price_low_to_high':
                $query->leftJoin('product_prices as pp_sort', function ($join) {
                    $join->on('products.id', '=', 'pp_sort.product_id')
                         ->whereNull('pp_sort.deleted_at')
                         ->where('pp_sort.is_current', true);
                })->orderBy('pp_sort.price', 'asc');
                break;

            case 'price_high_to_low':
                $query->leftJoin('product_prices as pp_sort', function ($join) {
                    $join->on('products.id', '=', 'pp_sort.product_id')
                         ->whereNull('pp_sort.deleted_at')
                         ->where('pp_sort.is_current', true);
                })->orderBy('pp_sort.price', 'desc');
                break;

            case 'rating':
                $query->orderBy('average_rating', $sortOrder);
                break;

            case 'popularity':
                $query->orderBy('view_count', $sortOrder);
                break;

            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;

            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'name':
                $query->orderBy('name', $sortOrder);
                break;

            case 'discount':
                $query->leftJoin('product_prices as pp_discount', function ($join) {
                    $join->on('products.id', '=', 'pp_discount.product_id')
                         ->whereNull('pp_discount.deleted_at')
                         ->where('pp_discount.is_current', true);
                })->orderBy('pp_discount.discount_percentage', 'desc');
                break;

            case 'relevance':
            default:
                // For relevance, we'll use a combination of factors
                if (!empty($params['q'])) {
                    // If there's a search query, order by text relevance
                    $query->orderByRaw("
                        CASE 
                            WHEN name LIKE ? THEN 1
                            WHEN brand LIKE ? THEN 2
                            WHEN description LIKE ? THEN 3
                            ELSE 4
                        END, view_count DESC, average_rating DESC
                    ", [
                        '%' . $params['q'] . '%',
                        '%' . $params['q'] . '%',
                        '%' . $params['q'] . '%'
                    ]);
                } else {
                    // Default relevance: popularity + rating
                    $query->orderBy('view_count', 'desc')
                          ->orderBy('average_rating', 'desc');
                }
                break;
        }

        return $query;
    }

    /**
     * Get filter options for dynamic filtering
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('search_filter_options', 3600, function () {
            return [
                'categories' => $this->getCategoryOptions(),
                'brands' => $this->getBrandOptions(),
                'price_range' => $this->getPriceRange(),
                'sellers' => $this->getSellerOptions(),
                'sort_options' => $this->getSortOptions(),
            ];
        });
    }

    /**
     * Get category filter options
     */
    protected function getCategoryOptions(): array
    {
        return Category::select('id', 'name', 'slug', 'parent_id')
            ->whereHas('products', function ($q) {
                $q->where('status', 'approved')->where('is_active', true);
            })
            ->with('children')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get brand filter options
     */
    protected function getBrandOptions(): array
    {
        return Product::select('brand')
            ->where('status', 'approved')
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->groupBy('brand')
            ->orderBy('brand')
            ->pluck('brand')
            ->values()
            ->toArray();
    }

    /**
     * Get price range for filtering
     */
    protected function getPriceRange(): array
    {
        $priceStats = DB::table('product_prices')
            ->join('products', 'products.id', '=', 'product_prices.product_id')
            ->where('products.status', 'approved')
            ->where('products.is_active', true)
            ->where('product_prices.is_current', true)
            ->whereNull('product_prices.deleted_at')
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price')
            ->first();

        return [
            'min' => (float)($priceStats->min_price ?? 0),
            'max' => (float)($priceStats->max_price ?? 0),
            'avg' => (float)($priceStats->avg_price ?? 0),
        ];
    }

    /**
     * Get seller filter options
     */
    protected function getSellerOptions(): array
    {
        return Seller::select('id', 'name', 'company_name', 'logo')
            ->whereHas('products', function ($q) {
                $q->where('status', 'approved')->where('is_active', true);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($seller) {
                return [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'company_name' => $seller->company_name,
                    'display_name' => $seller->company_name ?: $seller->name,
                    'logo' => $seller->logo,
                ];
            })
            ->toArray();
    }

    /**
     * Get sort options
     */
    protected function getSortOptions(): array
    {
        return [
            ['value' => 'relevance', 'label' => 'En Uygun'],
            ['value' => 'price_low_to_high', 'label' => 'Fiyat (Düşükten Yükseğe)'],
            ['value' => 'price_high_to_low', 'label' => 'Fiyat (Yüksekten Düşüğe)'],
            ['value' => 'rating', 'label' => 'En Yüksek Puan'],
            ['value' => 'popularity', 'label' => 'En Popüler'],
            ['value' => 'newest', 'label' => 'En Yeni'],
            ['value' => 'discount', 'label' => 'En Yüksek İndirim'],
            ['value' => 'name', 'label' => 'İsim (A-Z)'],
        ];
    }

    /**
     * Get search suggestions
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        return Cache::remember("search_suggestions_{$query}_{$limit}", 1800, function () use ($query, $limit) {
            $suggestions = [];

            // Product name suggestions
            $productSuggestions = Product::select('name')
                ->where('status', 'approved')
                ->where('is_active', true)
                ->where('name', 'LIKE', "%{$query}%")
                ->distinct()
                ->limit($limit)
                ->pluck('name')
                ->toArray();

            // Brand suggestions
            $brandSuggestions = Product::select('brand')
                ->where('status', 'approved')
                ->where('is_active', true)
                ->whereNotNull('brand')
                ->where('brand', 'LIKE', "%{$query}%")
                ->distinct()
                ->limit($limit)
                ->pluck('brand')
                ->toArray();

            // Category suggestions
            $categorySuggestions = Category::select('name')
                ->where('name', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->pluck('name')
                ->toArray();

            // Combine and prioritize suggestions
            $suggestions = array_merge(
                array_slice($productSuggestions, 0, 5),
                array_slice($brandSuggestions, 0, 3),
                array_slice($categorySuggestions, 0, 2)
            );

            return array_unique(array_slice($suggestions, 0, $limit));
        });
    }

    /**
     * Get popular searches
     */
    public function getPopularSearches(int $limit = 10): array
    {
        return Cache::remember("popular_searches_{$limit}", 3600, function () use ($limit) {
            // This would typically come from search analytics
            // For now, return some sample popular searches
            return [
                'iPhone',
                'Samsung Galaxy',
                'Laptop',
                'Kulaklık',
                'Telefon Kılıfı',
                'Powerbank',
                'Bluetooth Hoparlör',
                'Tablet',
                'Akıllı Saat',
                'Kamera'
            ];
        });
    }

    /**
     * Log search query for analytics
     */
    public function logSearch(string $query, int $resultCount, ?int $userId = null): void
    {
        // Log search for analytics
        DB::table('search_logs')->insert([
            'query' => $query,
            'result_count' => $resultCount,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
