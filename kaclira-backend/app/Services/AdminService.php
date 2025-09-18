<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Category;
use App\Models\ProductPrice;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdminService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(string $period = '30d'): array
    {
        $cacheKey = "admin_dashboard_stats_{$period}";
        
        return Cache::remember($cacheKey, 600, function () use ($period) {
            $startDate = $this->getStartDate($period);
            
            return [
                'overview' => $this->getOverviewStats($startDate),
                'users' => $this->getUserStats($startDate),
                'products' => $this->getProductStats($startDate),
                'sellers' => $this->getSellerStats($startDate),
                'revenue' => $this->getRevenueStats($startDate),
                'activity' => $this->getActivityStats($startDate),
                'charts' => $this->getChartData($period)
            ];
        });
    }

    /**
     * Get overview statistics
     */
    protected function getOverviewStats(Carbon $startDate): array
    {
        $totalUsers = User::count();
        $newUsers = User::where('created_at', '>=', $startDate)->count();
        
        $totalProducts = Product::count();
        $newProducts = Product::where('created_at', '>=', $startDate)->count();
        
        $totalSellers = Seller::count();
        $activeSellers = Seller::where('is_active', true)->count();
        
        $pendingProducts = Product::where('status', 'pending')->count();
        
        return [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'users_growth' => $totalUsers > 0 ? round(($newUsers / $totalUsers) * 100, 2) : 0,
            
            'total_products' => $totalProducts,
            'new_products' => $newProducts,
            'products_growth' => $totalProducts > 0 ? round(($newProducts / $totalProducts) * 100, 2) : 0,
            
            'total_sellers' => $totalSellers,
            'active_sellers' => $activeSellers,
            'sellers_activity_rate' => $totalSellers > 0 ? round(($activeSellers / $totalSellers) * 100, 2) : 0,
            
            'pending_products' => $pendingProducts,
            'pending_notifications' => Notification::whereNull('read_at')->count()
        ];
    }

    /**
     * Get user statistics
     */
    protected function getUserStats(Carbon $startDate): array
    {
        $usersByRole = $this->getUsersByRole();

        $newUsersByDay = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $activeUsers = User::where('last_login_at', '>=', now()->subDays(7))->count();
        
        return [
            'by_role' => $usersByRole,
            'new_by_day' => $newUsersByDay,
            'active_users' => $activeUsers,
            'inactive_users' => User::whereNull('last_login_at')
                ->orWhere('last_login_at', '<', now()->subDays(30))
                ->count()
        ];
    }

    /**
     * Get product statistics
     */
    protected function getProductStats(Carbon $startDate): array
    {
        $productsByStatus = Product::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $productsByCategory = Product::join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('count(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'name')
            ->toArray();

        $newProductsByDay = Product::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'by_status' => $productsByStatus,
            'by_category' => $productsByCategory,
            'new_by_day' => $newProductsByDay,
            'avg_price' => ProductPrice::where('is_current', true)->avg('price'),
            'total_value' => ProductPrice::where('is_current', true)
                ->sum(DB::raw('price * stock_quantity'))
        ];
    }

    /**
     * Get seller statistics
     */
    protected function getSellerStats(Carbon $startDate): array
    {
        $sellersByStatus = Seller::select(
                DB::raw('CASE WHEN is_active = 1 THEN "active" ELSE "inactive" END as status'),
                DB::raw('count(*) as count')
            )
            ->groupBy('is_active')
            ->pluck('count', 'status')
            ->toArray();

        $topSellers = Seller::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($seller) {
                return [
                    'id' => $seller->id,
                    'name' => $seller->company_name ?: $seller->name,
                    'products_count' => $seller->products_count,
                    'rating' => $seller->rating
                ];
            })
            ->toArray();

        return [
            'by_status' => $sellersByStatus,
            'top_sellers' => $topSellers,
            'avg_rating' => Seller::avg('rating'),
            'verified_sellers' => Seller::where('is_verified', true)->count()
        ];
    }

    /**
     * Get revenue statistics (placeholder for future implementation)
     */
    protected function getRevenueStats(Carbon $startDate): array
    {
        // This would be implemented when order/payment system is added
        return [
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'commission_earned' => 0,
            'pending_payments' => 0
        ];
    }

    /**
     * Get activity statistics
     */
    protected function getActivityStats(Carbon $startDate): array
    {
        $searchLogs = DB::table('search_logs')
            ->where('created_at', '>=', $startDate)
            ->count();

        $notifications = Notification::where('created_at', '>=', $startDate)->count();

        return [
            'total_searches' => $searchLogs,
            'notifications_sent' => $notifications,
            'product_views' => Product::sum('view_count'),
            'system_health' => 'good' // This would be calculated based on various metrics
        ];
    }

    /**
     * Get chart data for dashboard
     */
    protected function getChartData(string $period): array
    {
        $days = $this->getPeriodDays($period);
        $startDate = now()->subDays($days);

        // User registration chart
        $userChart = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Product creation chart
        $productChart = Product::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Search activity chart
        $searchChart = DB::table('search_logs')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'users' => $this->fillMissingDates($userChart, $days),
            'products' => $this->fillMissingDates($productChart, $days),
            'searches' => $this->fillMissingDates($searchChart, $days)
        ];
    }

    /**
     * Get user management data
     */
    public function getUserManagementData(array $filters = []): array
    {
        $query = User::query();

        // Apply filters
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true)
                      ->where('last_login_at', '>=', now()->subDays(30));
            } elseif ($filters['status'] === 'inactive') {
                $query->where(function ($q) {
                    $q->where('is_active', false)
                      ->orWhere('last_login_at', '<', now()->subDays(30))
                      ->orWhereNull('last_login_at');
                });
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->with('roles')
            ->withCount(['favorites', 'notifications'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);

        return [
            'users' => $users,
            'summary' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)
                    ->where('last_login_at', '>=', now()->subDays(30))->count(),
                'by_role' => $this->getUsersByRole()
            ]
        ];
    }

    /**
     * Get users count by role
     */
    private function getUsersByRole(): array
    {
        $roles = ['admin', 'seller', 'user', 'super_admin'];
        $result = [];
        
        foreach ($roles as $role) {
            $result[$role] = User::whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            })->count();
        }
        
        return $result;
    }

    /**
     * Get users count by role in a specific period
     */
    private function getUsersByRoleInPeriod($startDate, $endDate): array
    {
        $roles = ['admin', 'seller', 'user', 'super_admin'];
        $result = [];
        
        foreach ($roles as $role) {
            $result[$role] = User::whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            })->whereBetween('created_at', [$startDate, $endDate])->count();
        }
        
        return $result;
    }

    /**
     * Get seller management data
     */
    public function getSellerManagementData(array $filters = []): array
    {
        $query = Seller::query();

        // Apply filters
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            } elseif ($filters['status'] === 'verified') {
                $query->where('is_verified', true);
            } elseif ($filters['status'] === 'unverified') {
                $query->where('is_verified', false);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $sellers = $query->withCount(['products', 'subSellers'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);

        return [
            'sellers' => $sellers,
            'summary' => [
                'total' => Seller::count(),
                'active' => Seller::where('is_active', true)->count(),
                'verified' => Seller::where('is_verified', true)->count(),
                'avg_rating' => Seller::avg('rating')
            ]
        ];
    }

    /**
     * Get product approval data
     */
    public function getProductApprovalData(array $filters = []): array
    {
        $query = Product::with(['category', 'seller', 'currentPrice']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'pending'); // Default to pending
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%")
                  ->orWhere('barcode', $search);
            });
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);

        return [
            'products' => $products,
            'summary' => [
                'pending' => Product::where('status', 'pending')->count(),
                'approved' => Product::where('status', 'approved')->count(),
                'rejected' => Product::where('status', 'rejected')->count(),
                'total' => Product::count()
            ]
        ];
    }

    /**
     * Bulk approve products
     */
    public function bulkApproveProducts(array $productIds, int $adminId): array
    {
        $updated = Product::whereIn('id', $productIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'updated_at' => now()
            ]);

        return [
            'updated' => $updated,
            'message' => "{$updated} products approved successfully"
        ];
    }

    /**
     * Bulk reject products
     */
    public function bulkRejectProducts(array $productIds, int $adminId, string $reason = null): array
    {
        $updated = Product::whereIn('id', $productIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'rejected_by' => $adminId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'updated_at' => now()
            ]);

        return [
            'updated' => $updated,
            'message' => "{$updated} products rejected successfully"
        ];
    }

    /**
     * Generate system report
     */
    public function generateReport(string $type, array $params = []): array
    {
        switch ($type) {
            case 'users':
                return $this->generateUserReport($params);
            case 'products':
                return $this->generateProductReport($params);
            case 'sellers':
                return $this->generateSellerReport($params);
            case 'activity':
                return $this->generateActivityReport($params);
            default:
                throw new \InvalidArgumentException("Unknown report type: {$type}");
        }
    }

    /**
     * Generate user report
     */
    protected function generateUserReport(array $params): array
    {
        $startDate = $params['start_date'] ?? now()->subMonth();
        $endDate = $params['end_date'] ?? now();

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'registrations' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'by_role' => $this->getUsersByRoleInPeriod($startDate, $endDate),
            'active_users' => User::where('last_login_at', '>=', $startDate)->count(),
            'retention_rate' => $this->calculateUserRetention($startDate, $endDate)
        ];
    }

    /**
     * Generate product report
     */
    protected function generateProductReport(array $params): array
    {
        $startDate = $params['start_date'] ?? now()->subMonth();
        $endDate = $params['end_date'] ?? now();

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'new_products' => Product::whereBetween('created_at', [$startDate, $endDate])->count(),
            'by_status' => Product::whereBetween('created_at', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_category' => Product::join('categories', 'products.category_id', '=', 'categories.id')
                ->whereBetween('products.created_at', [$startDate, $endDate])
                ->select('categories.name', DB::raw('count(*) as count'))
                ->groupBy('categories.id', 'categories.name')
                ->orderBy('count', 'desc')
                ->pluck('count', 'name')
                ->toArray()
        ];
    }

    /**
     * Generate seller report
     */
    protected function generateSellerReport(array $params): array
    {
        $startDate = $params['start_date'] ?? now()->subMonth();
        $endDate = $params['end_date'] ?? now();

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'new_sellers' => Seller::whereBetween('created_at', [$startDate, $endDate])->count(),
            'active_sellers' => Seller::where('is_active', true)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
            'top_performers' => Seller::withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($seller) {
                    return [
                        'name' => $seller->company_name ?: $seller->name,
                        'products_count' => $seller->products_count,
                        'rating' => $seller->rating
                    ];
                })
                ->toArray()
        ];
    }

    /**
     * Generate activity report
     */
    protected function generateActivityReport(array $params): array
    {
        $startDate = $params['start_date'] ?? now()->subMonth();
        $endDate = $params['end_date'] ?? now();

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'searches' => DB::table('search_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'popular_searches' => DB::table('search_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('query', DB::raw('count(*) as count'))
                ->groupBy('query')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'query')
                ->toArray(),
            'notifications_sent' => Notification::whereBetween('created_at', [$startDate, $endDate])->count()
        ];
    }

    /**
     * Helper methods
     */
    protected function getStartDate(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30)
        };
    }

    protected function getPeriodDays(string $period): int
    {
        return match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };
    }

    protected function fillMissingDates(array $data, int $days): array
    {
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[$date] = $data[$date] ?? 0;
        }
        return $result;
    }

    protected function calculateUserRetention(Carbon $startDate, Carbon $endDate): float
    {
        $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeUsers = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('last_login_at', '>=', $endDate->copy()->subDays(7))
            ->count();

        return $newUsers > 0 ? round(($activeUsers / $newUsers) * 100, 2) : 0;
    }
}
