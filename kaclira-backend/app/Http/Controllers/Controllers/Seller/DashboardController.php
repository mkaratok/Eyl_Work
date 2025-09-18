<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Services\SellerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class DashboardController extends Controller
{
    protected SellerService $sellerService;

    public function __construct(SellerService $sellerService)
    {
        $this->sellerService = $sellerService;
    }

    /**
     * Get seller dashboard statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seller profile not found'
                ], 404);
            }

            $stats = $seller->getDashboardStats();

            // Add additional dashboard-specific metrics
            $dashboardStats = array_merge($stats, [
                'subscription_info' => [
                    'type' => $seller->subscription_type,
                    'expires_at' => $seller->subscription_expires_at,
                    'is_active' => $seller->subscription_active,
                    'days_remaining' => $seller->subscription_expires_at 
                        ? $seller->subscription_expires_at->diffInDays(now()) 
                        : 0,
                ],
                'seller_info' => [
                    'company_name' => $seller->company_name,
                    'status' => $seller->status,
                    'is_parent' => $seller->is_parent,
                    'commission_rate' => $seller->commission_rate,
                    'logo_url' => $seller->logo_url,
                ],
                'limits' => [
                    'max_sub_sellers' => $seller->getMaxSubSellersLimit(),
                    'can_create_sub_seller' => $seller->canCreateSubSeller(),
                ],
                'recent_activity' => $this->getRecentActivity($seller),
                'alerts' => $this->getDashboardAlerts($seller),
            ]);

            return response()->json([
                'success' => true,
                'data' => $dashboardStats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seller analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller->hasPermission(Seller::PERMISSION_VIEW_ANALYTICS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view analytics'
                ], 403);
            }

            $period = $request->get('period', 'month');
            $limit = min($request->get('limit', 12), 24);
            $includeSubSellers = $request->boolean('include_sub_sellers', false);

            $analytics = $this->sellerService->getSellerAnalytics($seller, [
                'period' => $period,
                'limit' => $limit,
                'include_sub_sellers' => $includeSubSellers && $seller->is_parent,
            ]);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue chart data
     */
    public function revenueChart(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller->hasPermission(Seller::PERMISSION_VIEW_ANALYTICS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view analytics'
                ], 403);
            }

            $period = $request->get('period', 'month');
            $limit = min($request->get('limit', 12), 24);

            $revenueData = $seller->getRevenueByPeriod($period, $limit);

            // Format for chart
            $chartData = [
                'labels' => array_reverse(array_column($revenueData, 'period')),
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => array_reverse(array_column($revenueData, 'revenue')),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 2,
                        'fill' => true,
                    ],
                    [
                        'label' => 'Orders',
                        'data' => array_reverse(array_column($revenueData, 'orders_count')),
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'borderColor' => 'rgb(16, 185, 129)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'yAxisID' => 'y1',
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top selling products
     */
    public function topProducts(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;
            $limit = min($request->get('limit', 10), 50);

            $topProducts = $seller->getTopSellingProducts($limit);

            return response()->json([
                'success' => true,
                'data' => $topProducts
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch top products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent orders
     */
    public function recentOrders(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;
            $limit = min($request->get('limit', 10), 50);

            $recentOrders = $seller->orders()
                ->with(['user', 'orderItems.productPrice.product'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->user->name ?? 'Guest',
                        'customer_email' => $order->user->email ?? $order->guest_email,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'items_count' => $order->orderItems->count(),
                        'created_at' => $order->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentOrders
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock products
     */
    public function lowStockProducts(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;
            $threshold = $request->get('threshold', 10);
            $limit = min($request->get('limit', 20), 100);

            $lowStockProducts = $seller->productPrices()
                ->with('product')
                ->where('stock', '<=', $threshold)
                ->where('stock', '>', 0)
                ->orderBy('stock', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($productPrice) {
                    return [
                        'id' => $productPrice->id,
                        'product_id' => $productPrice->product_id,
                        'product_name' => $productPrice->product->name,
                        'product_sku' => $productPrice->product->sku,
                        'product_image' => $productPrice->product->image_url,
                        'price' => $productPrice->price,
                        'stock' => $productPrice->stock,
                        'is_active' => $productPrice->is_active,
                        'updated_at' => $productPrice->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $lowStockProducts
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch low stock products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function performanceMetrics(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller->hasPermission(Seller::PERMISSION_VIEW_ANALYTICS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view analytics'
                ], 403);
            }

            $analytics = $this->sellerService->getSellerAnalytics($seller);
            $performanceMetrics = $analytics['performance_metrics'] ?? [];

            return response()->json([
                'success' => true,
                'data' => $performanceMetrics
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;
            $limit = min($request->get('limit', 20), 100);
            $unreadOnly = $request->boolean('unread_only', false);

            $query = $seller->notifications()->latest();

            if ($unreadOnly) {
                $query->whereNull('read_at');
            }

            $notifications = $query->limit($limit)->get()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'meta' => [
                    'unread_count' => $seller->notifications()->whereNull('read_at')->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            $notification = $seller->notifications()->findOrFail($notificationId);
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            $seller->notifications()->whereNull('read_at')->update([
                'read_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activity for dashboard
     */
    protected function getRecentActivity(Seller $seller): array
    {
        $activities = [];

        // Recent orders
        $recentOrders = $seller->orders()
            ->latest()
            ->limit(5)
            ->get(['id', 'order_number', 'total_amount', 'status', 'created_at']);

        foreach ($recentOrders as $order) {
            $activities[] = [
                'type' => 'order',
                'title' => "New order #{$order->order_number}",
                'description' => "Order worth " . number_format($order->total_amount, 2) . " TL",
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        }

        // Recent product updates
        $recentProducts = $seller->productPrices()
            ->with('product')
            ->latest('updated_at')
            ->limit(3)
            ->get();

        foreach ($recentProducts as $productPrice) {
            $activities[] = [
                'type' => 'product',
                'title' => "Product updated: {$productPrice->product->name}",
                'description' => "Price: " . number_format($productPrice->price, 2) . " TL, Stock: {$productPrice->stock}",
                'status' => $productPrice->is_active ? 'active' : 'inactive',
                'created_at' => $productPrice->updated_at,
            ];
        }

        // Sort by date and limit
        usort($activities, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get dashboard alerts
     */
    protected function getDashboardAlerts(Seller $seller): array
    {
        $alerts = [];

        // Subscription expiring soon
        if ($seller->subscription_expires_at && $seller->subscription_expires_at->diffInDays(now()) <= 30) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Subscription Expiring',
                'message' => "Your subscription expires in {$seller->subscription_expires_at->diffInDays(now())} days",
                'action_url' => '/seller/subscription',
            ];
        }

        // Low stock products
        $lowStockCount = $seller->getLowStockProductsCount();
        if ($lowStockCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => "You have {$lowStockCount} products with low stock",
                'action_url' => '/seller/products?filter=low_stock',
            ];
        }

        // Pending orders
        $pendingOrdersCount = $seller->orders()->where('status', 'pending')->count();
        if ($pendingOrdersCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Pending Orders',
                'message' => "You have {$pendingOrdersCount} orders waiting for processing",
                'action_url' => '/seller/orders?status=pending',
            ];
        }

        // Inactive products
        $inactiveProductsCount = $seller->productPrices()->where('is_active', false)->count();
        if ($inactiveProductsCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Inactive Products',
                'message' => "You have {$inactiveProductsCount} inactive products",
                'action_url' => '/seller/products?status=inactive',
            ];
        }

        return $alerts;
    }

    /**
     * Export dashboard data
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller->hasPermission(Seller::PERMISSION_EXPORT_DATA)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to export data'
                ], 403);
            }

            $dataType = $request->get('type', 'stats');
            $format = $request->get('format', 'json');

            $data = match ($dataType) {
                'stats' => $seller->getDashboardStats(),
                'analytics' => $this->sellerService->getSellerAnalytics($seller),
                'products' => $seller->productPrices()->with('product')->get(),
                'orders' => $seller->orders()->with('orderItems')->get(),
                default => ['error' => 'Invalid data type']
            };

            if ($format === 'csv') {
                // Convert to CSV format
                // This would require a CSV export service
                return response()->json([
                    'success' => false,
                    'message' => 'CSV export not implemented yet'
                ], 501);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'exported_at' => now(),
                'seller_id' => $seller->id,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
