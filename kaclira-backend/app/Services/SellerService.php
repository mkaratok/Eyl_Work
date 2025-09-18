<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\User;
use App\Models\ProductPrice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Exception;

class SellerService
{
    /**
     * Create a new sub-seller
     */
    public function createSubSeller(Seller $parentSeller, array $data): Seller
    {
        // Validate parent seller can create sub-sellers
        if (!$parentSeller->canCreateSubSeller()) {
            throw new Exception('Parent seller cannot create more sub-sellers or lacks permission.');
        }

        DB::beginTransaction();
        
        try {
            // Create seller record
            $subSeller = Seller::create([
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'parent_seller_id' => $parentSeller->id,
                'status' => Seller::STATUS_PENDING,
                'commission_rate' => $data['commission_rate'] ?? $parentSeller->commission_rate,
                'subscription_type' => $parentSeller->subscription_type,
                'subscription_expires_at' => $parentSeller->subscription_expires_at,
                'permissions' => $data['permissions'] ?? [
                    Seller::PERMISSION_MANAGE_PRODUCTS,
                    Seller::PERMISSION_MANAGE_PRICES,
                ],
                'settings' => $data['settings'] ?? [],
                'description' => $data['description'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'social_links' => $data['social_links'] ?? [],
            ]);

            // Create user account for sub-seller if provided
            if (isset($data['create_user']) && $data['create_user']) {
                $user = User::create([
                    'name' => $data['contact_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password'] ?? str()->random(12)),
                    'email_verified_at' => now(),
                ]);

                // Assign seller role
                $user->assignRole('seller');
                
                // Link user to seller
                $user->seller_id = $subSeller->id;
                $user->save();

                // Send welcome email
                if (isset($data['send_welcome_email']) && $data['send_welcome_email']) {
                    $this->sendWelcomeEmail($subSeller, $user, $data['password'] ?? null);
                }
            }

            // Handle logo upload
            if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
                $logoPath = $this->uploadLogo($data['logo'], $subSeller->id);
                $subSeller->update(['logo_path' => $logoPath]);
            }

            DB::commit();

            // Clear parent seller cache
            $parentSeller->clearStatsCache();

            return $subSeller;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update sub-seller permissions
     */
    public function updateSubSellerPermissions(Seller $parentSeller, Seller $subSeller, array $permissions): bool
    {
        // Validate parent-child relationship
        if ($subSeller->parent_seller_id !== $parentSeller->id) {
            throw new Exception('Invalid parent-child seller relationship.');
        }

        // Validate parent has permission to manage sub-sellers
        if (!$parentSeller->canManageSubSellers()) {
            throw new Exception('Parent seller lacks permission to manage sub-sellers.');
        }

        // Validate permissions
        $validPermissions = $subSeller->getAllPermissions();
        $invalidPermissions = array_diff($permissions, $validPermissions);
        
        if (!empty($invalidPermissions)) {
            throw new Exception('Invalid permissions: ' . implode(', ', $invalidPermissions));
        }

        // Update permissions
        $subSeller->syncPermissions($permissions);

        return true;
    }

    /**
     * Delegate permissions from parent to sub-seller
     */
    public function delegatePermissions(Seller $parentSeller, Seller $subSeller, array $permissions): bool
    {
        // Validate parent has the permissions to delegate
        foreach ($permissions as $permission) {
            if (!$parentSeller->hasPermission($permission)) {
                throw new Exception("Parent seller does not have permission: {$permission}");
            }
        }

        // Get current sub-seller permissions
        $currentPermissions = $subSeller->permissions ?? [];
        
        // Merge with new permissions
        $newPermissions = array_unique(array_merge($currentPermissions, $permissions));
        
        // Update sub-seller permissions
        $subSeller->syncPermissions($newPermissions);

        return true;
    }

    /**
     * Revoke permissions from sub-seller
     */
    public function revokePermissions(Seller $parentSeller, Seller $subSeller, array $permissions): bool
    {
        // Validate parent-child relationship
        if ($subSeller->parent_seller_id !== $parentSeller->id) {
            throw new Exception('Invalid parent-child seller relationship.');
        }

        // Get current permissions
        $currentPermissions = $subSeller->permissions ?? [];
        
        // Remove specified permissions
        $newPermissions = array_diff($currentPermissions, $permissions);
        
        // Update permissions
        $subSeller->syncPermissions($newPermissions);

        return true;
    }

    /**
     * Get seller analytics
     */
    public function getSellerAnalytics(Seller $seller, array $options = []): array
    {
        $period = $options['period'] ?? 'month';
        $limit = $options['limit'] ?? 12;
        $includeSubSellers = $options['include_sub_sellers'] ?? false;

        $cacheKey = "seller_analytics_{$seller->id}_{$period}_{$limit}_" . ($includeSubSellers ? 'with_subs' : 'solo');
        
        return Cache::remember($cacheKey, 600, function () use ($seller, $period, $limit, $includeSubSellers) {
            $analytics = [
                'dashboard_stats' => $seller->getDashboardStats(),
                'revenue_by_period' => $seller->getRevenueByPeriod($period, $limit),
                'top_selling_products' => $seller->getTopSellingProducts(10),
                'performance_metrics' => $this->calculatePerformanceMetrics($seller),
                'growth_metrics' => $this->calculateGrowthMetrics($seller, $period),
            ];

            // Include sub-sellers data if requested
            if ($includeSubSellers && $seller->is_parent) {
                $analytics['sub_sellers_stats'] = $this->getSubSellersAnalytics($seller);
                $analytics['consolidated_stats'] = $this->getConsolidatedStats($seller);
            }

            return $analytics;
        });
    }

    /**
     * Calculate performance metrics
     */
    protected function calculatePerformanceMetrics(Seller $seller): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        $lastYear = $now->copy()->subYear();

        return [
            'monthly_growth' => $this->calculateGrowthRate($seller, 'month'),
            'yearly_growth' => $this->calculateGrowthRate($seller, 'year'),
            'customer_retention' => $this->calculateCustomerRetention($seller),
            'order_fulfillment_rate' => $this->calculateOrderFulfillmentRate($seller),
            'average_delivery_time' => $this->calculateAverageDeliveryTime($seller),
            'customer_satisfaction' => $this->calculateCustomerSatisfaction($seller),
        ];
    }

    /**
     * Calculate growth metrics
     */
    protected function calculateGrowthMetrics(Seller $seller, string $period): array
    {
        $periods = $this->getPeriodRanges($period, 6);
        $metrics = [];

        foreach ($periods as $index => $periodRange) {
            $revenue = $seller->orders()
                ->where('status', 'completed')
                ->whereBetween('created_at', $periodRange)
                ->sum('total_amount');

            $orders = $seller->orders()
                ->whereBetween('created_at', $periodRange)
                ->count();

            $metrics[] = [
                'period' => $periodRange[0]->format('Y-m'),
                'revenue' => (float) $revenue,
                'orders' => $orders,
                'avg_order_value' => $orders > 0 ? $revenue / $orders : 0,
            ];
        }

        return array_reverse($metrics);
    }

    /**
     * Get sub-sellers analytics
     */
    protected function getSubSellersAnalytics(Seller $parentSeller): array
    {
        $subSellers = $parentSeller->childSellers()->active()->get();
        $analytics = [];

        foreach ($subSellers as $subSeller) {
            $analytics[] = [
                'seller_id' => $subSeller->id,
                'company_name' => $subSeller->company_name,
                'stats' => $subSeller->getDashboardStats(),
                'permissions' => $subSeller->permissions,
                'status' => $subSeller->status,
                'created_at' => $subSeller->created_at,
            ];
        }

        return $analytics;
    }

    /**
     * Get consolidated stats for parent and all sub-sellers
     */
    protected function getConsolidatedStats(Seller $parentSeller): array
    {
        $allSellers = collect([$parentSeller])->merge($parentSeller->childSellers);
        
        $consolidated = [
            'total_products' => 0,
            'active_products' => 0,
            'total_orders' => 0,
            'monthly_orders' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'sub_sellers_count' => $parentSeller->getSubSellersCount(),
        ];

        foreach ($allSellers as $seller) {
            $stats = $seller->getDashboardStats();
            
            $consolidated['total_products'] += $stats['total_products'];
            $consolidated['active_products'] += $stats['active_products'];
            $consolidated['total_orders'] += $stats['total_orders'];
            $consolidated['monthly_orders'] += $stats['monthly_orders'];
            $consolidated['total_revenue'] += $stats['total_revenue'];
            $consolidated['monthly_revenue'] += $stats['monthly_revenue'];
        }

        // Calculate averages
        $consolidated['avg_order_value'] = $consolidated['total_orders'] > 0 
            ? $consolidated['total_revenue'] / $consolidated['total_orders'] 
            : 0;

        return $consolidated;
    }

    /**
     * Calculate growth rate
     */
    protected function calculateGrowthRate(Seller $seller, string $period): float
    {
        $now = Carbon::now();
        
        if ($period === 'month') {
            $currentStart = $now->copy()->startOfMonth();
            $currentEnd = $now->copy()->endOfMonth();
            $previousStart = $now->copy()->subMonth()->startOfMonth();
            $previousEnd = $now->copy()->subMonth()->endOfMonth();
        } else {
            $currentStart = $now->copy()->startOfYear();
            $currentEnd = $now->copy()->endOfYear();
            $previousStart = $now->copy()->subYear()->startOfYear();
            $previousEnd = $now->copy()->subYear()->endOfYear();
        }

        $currentRevenue = $seller->orders()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('total_amount');

        $previousRevenue = $seller->orders()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total_amount');

        if ($previousRevenue == 0) {
            return $currentRevenue > 0 ? 100 : 0;
        }

        return (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
    }

    /**
     * Calculate customer retention rate
     */
    protected function calculateCustomerRetention(Seller $seller): float
    {
        // This would require order customer tracking
        // Placeholder implementation
        return 75.5;
    }

    /**
     * Calculate order fulfillment rate
     */
    protected function calculateOrderFulfillmentRate(Seller $seller): float
    {
        $totalOrders = $seller->orders()->count();
        $fulfilledOrders = $seller->orders()->where('status', 'completed')->count();

        return $totalOrders > 0 ? ($fulfilledOrders / $totalOrders) * 100 : 0;
    }

    /**
     * Calculate average delivery time
     */
    protected function calculateAverageDeliveryTime(Seller $seller): float
    {
        // This would require delivery tracking
        // Placeholder implementation
        return 3.2; // days
    }

    /**
     * Calculate customer satisfaction
     */
    protected function calculateCustomerSatisfaction(Seller $seller): float
    {
        // This would require review/rating system
        // Placeholder implementation
        return 4.3; // out of 5
    }

    /**
     * Get period ranges for analytics
     */
    protected function getPeriodRanges(string $period, int $count): array
    {
        $ranges = [];
        $now = Carbon::now();

        for ($i = 0; $i < $count; $i++) {
            if ($period === 'month') {
                $start = $now->copy()->subMonths($i)->startOfMonth();
                $end = $now->copy()->subMonths($i)->endOfMonth();
            } elseif ($period === 'week') {
                $start = $now->copy()->subWeeks($i)->startOfWeek();
                $end = $now->copy()->subWeeks($i)->endOfWeek();
            } else {
                $start = $now->copy()->subDays($i)->startOfDay();
                $end = $now->copy()->subDays($i)->endOfDay();
            }

            $ranges[] = [$start, $end];
        }

        return $ranges;
    }

    /**
     * Upload seller logo
     */
    protected function uploadLogo(UploadedFile $file, int $sellerId): string
    {
        $filename = "seller_{$sellerId}_logo." . $file->getClientOriginalExtension();
        $path = $file->storeAs('sellers/logos', $filename, 'public');
        
        return $path;
    }

    /**
     * Send welcome email to new sub-seller
     */
    protected function sendWelcomeEmail(Seller $seller, User $user, ?string $password = null): void
    {
        // Implementation would depend on your mail setup
        // Mail::to($user->email)->send(new SubSellerWelcomeMail($seller, $user, $password));
    }

    /**
     * Approve seller
     */
    public function approveSeller(Seller $seller): bool
    {
        $seller->update(['status' => Seller::STATUS_ACTIVE]);
        
        // Send approval notification
        // $this->sendApprovalNotification($seller);
        
        return true;
    }

    /**
     * Reject seller
     */
    public function rejectSeller(Seller $seller, string $reason = null): bool
    {
        $seller->update([
            'status' => Seller::STATUS_REJECTED,
            'settings' => array_merge($seller->settings ?? [], ['rejection_reason' => $reason])
        ]);
        
        // Send rejection notification
        // $this->sendRejectionNotification($seller, $reason);
        
        return true;
    }

    /**
     * Suspend seller
     */
    public function suspendSeller(Seller $seller, string $reason = null): bool
    {
        $seller->update([
            'status' => Seller::STATUS_SUSPENDED,
            'settings' => array_merge($seller->settings ?? [], ['suspension_reason' => $reason])
        ]);
        
        // Deactivate all products
        $seller->productPrices()->update(['is_active' => false]);
        
        return true;
    }

    /**
     * Reactivate seller
     */
    public function reactivateSeller(Seller $seller): bool
    {
        $seller->update(['status' => Seller::STATUS_ACTIVE]);
        
        // Reactivate products (optional)
        // $seller->productPrices()->update(['is_active' => true]);
        
        return true;
    }

    /**
     * Delete seller and cleanup
     */
    public function deleteSeller(Seller $seller): bool
    {
        DB::beginTransaction();
        
        try {
            // Delete sub-sellers first
            foreach ($seller->childSellers as $subSeller) {
                $this->deleteSeller($subSeller);
            }

            // Deactivate all product prices
            $seller->productPrices()->delete();

            // Delete related user account
            if ($seller->user) {
                $seller->user->delete();
            }

            // Delete logo file
            if ($seller->logo_path) {
                Storage::disk('public')->delete($seller->logo_path);
            }

            // Clear cache
            $seller->clearStatsCache();

            // Delete seller
            $seller->delete();

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update seller subscription
     */
    public function updateSubscription(Seller $seller, string $subscriptionType, Carbon $expiresAt): bool
    {
        $seller->update([
            'subscription_type' => $subscriptionType,
            'subscription_expires_at' => $expiresAt
        ]);

        // Update sub-sellers subscription as well
        $seller->childSellers()->update([
            'subscription_type' => $subscriptionType,
            'subscription_expires_at' => $expiresAt
        ]);

        return true;
    }

    /**
     * Get seller hierarchy
     */
    public function getSellerHierarchy(Seller $seller): array
    {
        if ($seller->is_child) {
            $seller = $seller->parentSeller;
        }

        return [
            'parent' => [
                'id' => $seller->id,
                'company_name' => $seller->company_name,
                'status' => $seller->status,
                'stats' => $seller->getDashboardStats(),
            ],
            'children' => $seller->childSellers->map(function ($child) {
                return [
                    'id' => $child->id,
                    'company_name' => $child->company_name,
                    'status' => $child->status,
                    'permissions' => $child->permissions,
                    'stats' => $child->getDashboardStats(),
                ];
            })->toArray()
        ];
    }
}
