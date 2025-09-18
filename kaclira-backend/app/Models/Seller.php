<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Seller extends Model
{
    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'tax_number',
        'status',
        'parent_seller_id',
        'commission_rate',
        'subscription_type',
        'subscription_expires_at',
        'permissions',
        'settings',
        'logo_path',
        'description',
        'website_url',
        'social_links',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'commission_rate' => 'decimal:2',
            'subscription_expires_at' => 'datetime',
            'permissions' => 'array',
            'settings' => 'array',
            'social_links' => 'array',
        ];
    }

    // Constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REJECTED = 'rejected';

    const SUBSCRIPTION_BASIC = 'basic';
    const SUBSCRIPTION_PREMIUM = 'premium';
    const SUBSCRIPTION_ENTERPRISE = 'enterprise';

    const PERMISSION_MANAGE_PRODUCTS = 'manage_products';
    const PERMISSION_MANAGE_PRICES = 'manage_prices';
    const PERMISSION_MANAGE_ORDERS = 'manage_orders';
    const PERMISSION_MANAGE_SUB_SELLERS = 'manage_sub_sellers';
    const PERMISSION_VIEW_ANALYTICS = 'view_analytics';
    const PERMISSION_EXPORT_DATA = 'export_data';

    // Relationships
    public function parentSeller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'parent_seller_id');
    }

    public function childSellers(): HasMany
    {
        return $this->hasMany(Seller::class, 'parent_seller_id');
    }

    public function allDescendants(): HasMany
    {
        return $this->childSellers()->with('allDescendants');
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, ProductPrice::class, 'seller_id', 'id', 'id', 'product_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'notifiable_id')->where('notifiable_type', self::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_seller_id');
    }

    public function scopeChildren(Builder $query): Builder
    {
        return $query->whereNotNull('parent_seller_id');
    }

    public function scopeWithSubscription(Builder $query, string $type): Builder
    {
        return $query->where('subscription_type', $type);
    }

    public function scopeSubscriptionActive(Builder $query): Builder
    {
        return $query->where('subscription_expires_at', '>', now());
    }

    // Accessors & Mutators
    public function getIsParentAttribute(): bool
    {
        return is_null($this->parent_seller_id);
    }

    public function getIsChildAttribute(): bool
    {
        return !is_null($this->parent_seller_id);
    }

    public function getHasChildrenAttribute(): bool
    {
        return $this->childSellers()->exists();
    }

    public function getSubscriptionActiveAttribute(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    // Permission Management
    public function hasPermission(string $permission): bool
    {
        if ($this->is_parent) {
            return true; // Parent sellers have all permissions
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        $this->update(['permissions' => array_values($permissions)]);
    }

    public function syncPermissions(array $permissions): void
    {
        $this->update(['permissions' => $permissions]);
    }

    public function getAllPermissions(): array
    {
        return [
            self::PERMISSION_MANAGE_PRODUCTS,
            self::PERMISSION_MANAGE_PRICES,
            self::PERMISSION_MANAGE_ORDERS,
            self::PERMISSION_MANAGE_SUB_SELLERS,
            self::PERMISSION_VIEW_ANALYTICS,
            self::PERMISSION_EXPORT_DATA,
        ];
    }

    // Statistics Methods
    public function getDashboardStats(): array
    {
        $cacheKey = "seller_stats_{$this->id}";
        
        return Cache::remember($cacheKey, 300, function () {
            $stats = [
                'total_products' => $this->getTotalProductsCount(),
                'active_products' => $this->getActiveProductsCount(),
                'total_orders' => $this->getTotalOrdersCount(),
                'monthly_orders' => $this->getMonthlyOrdersCount(),
                'total_revenue' => $this->getTotalRevenue(),
                'monthly_revenue' => $this->getMonthlyRevenue(),
                'avg_order_value' => $this->getAverageOrderValue(),
                'conversion_rate' => $this->getConversionRate(),
                'product_views' => $this->getProductViews(),
                'favorites_count' => $this->getFavoritesCount(),
                'sub_sellers_count' => $this->getSubSellersCount(),
                'low_stock_products' => $this->getLowStockProductsCount(),
            ];

            return $stats;
        });
    }

    public function getTotalProductsCount(): int
    {
        return $this->productPrices()->distinct('product_id')->count();
    }

    public function getActiveProductsCount(): int
    {
        return $this->productPrices()->active()->distinct('product_id')->count();
    }

    public function getTotalOrdersCount(): int
    {
        return $this->orders()->count();
    }

    public function getMonthlyOrdersCount(): int
    {
        return $this->orders()
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->count();
    }

    public function getTotalRevenue(): float
    {
        return (float) $this->orders()
            ->where('status', 'completed')
            ->sum('total_amount');
    }

    public function getMonthlyRevenue(): float
    {
        return (float) $this->orders()
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->sum('total_amount');
    }

    public function getAverageOrderValue(): float
    {
        $totalRevenue = $this->getTotalRevenue();
        $totalOrders = $this->orders()->where('status', 'completed')->count();
        
        return $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
    }

    public function getConversionRate(): float
    {
        $views = $this->getProductViews();
        $orders = $this->getTotalOrdersCount();
        
        return $views > 0 ? ($orders / $views) * 100 : 0;
    }

    public function getProductViews(): int
    {
        // This would require a product_views table or analytics integration
        return 0; // Placeholder
    }

    public function getFavoritesCount(): int
    {
        return DB::table('user_favorites')
            ->join('product_prices', 'user_favorites.product_id', '=', 'product_prices.product_id')
            ->where('product_prices.seller_id', $this->id)
            ->distinct('user_favorites.product_id')
            ->count();
    }

    public function getSubSellersCount(): int
    {
        return $this->childSellers()->count();
    }

    public function getLowStockProductsCount(): int
    {
        return $this->productPrices()
            ->where('stock', '<=', 10)
            ->where('stock', '>', 0)
            ->count();
    }

    public function getRevenueByPeriod(string $period = 'month', int $limit = 12): array
    {
        $cacheKey = "seller_revenue_{$this->id}_{$period}_{$limit}";
        
        return Cache::remember($cacheKey, 600, function () use ($period, $limit) {
            $query = $this->orders()
                ->where('status', 'completed')
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                    DB::raw('SUM(total_amount) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                )
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit($limit);

            if ($period === 'week') {
                $query->select(
                    DB::raw("YEARWEEK(created_at) as period"),
                    DB::raw('SUM(total_amount) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                );
            } elseif ($period === 'day') {
                $query->select(
                    DB::raw("DATE(created_at) as period"),
                    DB::raw('SUM(total_amount) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                );
            }

            return $query->get()->toArray();
        });
    }

    public function getTopSellingProducts(int $limit = 10): array
    {
        $cacheKey = "seller_top_products_{$this->id}_{$limit}";
        
        return Cache::remember($cacheKey, 600, function () use ($limit) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('product_prices', 'order_items.product_price_id', '=', 'product_prices.id')
                ->join('products', 'product_prices.product_id', '=', 'products.id')
                ->where('product_prices.seller_id', $this->id)
                ->where('orders.status', 'completed')
                ->select(
                    'products.id',
                    'products.name',
                    'products.image_path',
                    DB::raw('SUM(order_items.quantity) as total_sold'),
                    DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.image_path')
                ->orderBy('total_sold', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    // Helper Methods
    public function canManageSubSellers(): bool
    {
        return $this->is_parent || $this->hasPermission(self::PERMISSION_MANAGE_SUB_SELLERS);
    }

    public function canCreateSubSeller(): bool
    {
        if (!$this->canManageSubSellers()) {
            return false;
        }

        // Check subscription limits
        $currentSubSellers = $this->getSubSellersCount();
        $maxSubSellers = $this->getMaxSubSellersLimit();
        
        return $currentSubSellers < $maxSubSellers;
    }

    public function getMaxSubSellersLimit(): int
    {
        return match ($this->subscription_type) {
            self::SUBSCRIPTION_BASIC => 3,
            self::SUBSCRIPTION_PREMIUM => 10,
            self::SUBSCRIPTION_ENTERPRISE => 50,
            default => 1,
        };
    }

    public function clearStatsCache(): void
    {
        $patterns = [
            "seller_stats_{$this->id}",
            "seller_revenue_{$this->id}_*",
            "seller_top_products_{$this->id}_*",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Clear pattern-based cache keys
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                Cache::forget($pattern);
            }
        }
    }

    // Events
    protected static function booted(): void
    {
        static::updated(function (Seller $seller) {
            $seller->clearStatsCache();
        });

        static::deleted(function (Seller $seller) {
            $seller->clearStatsCache();
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
