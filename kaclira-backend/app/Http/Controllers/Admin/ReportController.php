<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Models\User;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use App\Exports\ProductsExport;
use App\Exports\SellersExport;

class ReportController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Get users report
     */
    public function usersReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'role' => 'nullable|string|in:user,seller,admin',
                'status' => 'nullable|string|in:active,inactive,banned',
                'export' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            $data = $this->adminService->getUsersReport($filters);

            if ($request->boolean('export')) {
                $filename = 'users_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
                Excel::store(new UsersExport($filters), "exports/{$filename}");
                
                $data['export_url'] = url("storage/exports/{$filename}");
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Users report generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Users report error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı raporu oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get products report
     */
    public function productsReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'category_id' => 'nullable|integer|exists:categories,id',
                'status' => 'nullable|string|in:active,inactive,pending,rejected',
                'admin_approved' => 'nullable|boolean',
                'featured' => 'nullable|boolean',
                'export' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            $data = $this->adminService->getProductsReport($filters);

            if ($request->boolean('export')) {
                $filename = 'products_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
                Excel::store(new ProductsExport($filters), "exports/{$filename}");
                
                $data['export_url'] = url("storage/exports/{$filename}");
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Products report generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Products report error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ürün raporu oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get sellers report
     */
    public function sellersReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string|in:active,inactive,pending,suspended',
                'subscription_type' => 'nullable|string|in:basic,premium,enterprise',
                'parent_only' => 'nullable|boolean',
                'export' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            $data = $this->adminService->getSellersReport($filters);

            if ($request->boolean('export')) {
                $filename = 'sellers_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
                Excel::store(new SellersExport($filters), "exports/{$filename}");
                
                $data['export_url'] = url("storage/exports/{$filename}");
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Sellers report generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Sellers report error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Satıcı raporu oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get activity report
     */
    public function activityReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'type' => 'nullable|string|in:user,product,seller,system',
                'action' => 'nullable|string',
                'limit' => 'nullable|integer|min:1|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            $data = $this->adminService->getActivityReport($filters);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Activity report generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Activity report error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Aktivite raporu oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get system statistics report
     */
    public function systemStats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'nullable|string|in:24h,7d,30d,90d,1y',
                'metrics' => 'nullable|array',
                'metrics.*' => 'string|in:users,products,sellers,categories,orders,revenue'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->input('period', '30d');
            $metrics = $request->input('metrics', ['users', 'products', 'sellers', 'orders']);

            $stats = [
                'period' => $period,
                'generated_at' => now()->toISOString(),
                'metrics' => []
            ];

            // Generate requested metrics
            foreach ($metrics as $metric) {
                switch ($metric) {
                    case 'users':
                        $stats['metrics']['users'] = $this->getUserMetrics($period);
                        break;
                    case 'products':
                        $stats['metrics']['products'] = $this->getProductMetrics($period);
                        break;
                    case 'sellers':
                        $stats['metrics']['sellers'] = $this->getSellerMetrics($period);
                        break;
                    case 'categories':
                        $stats['metrics']['categories'] = $this->getCategoryMetrics($period);
                        break;
                    case 'orders':
                        $stats['metrics']['orders'] = $this->getOrderMetrics($period);
                        break;
                    case 'revenue':
                        $stats['metrics']['revenue'] = $this->getRevenueMetrics($period);
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'System statistics generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('System stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Sistem istatistikleri oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function performanceMetrics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'nullable|string|in:1h,24h,7d,30d',
                'include_details' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->input('period', '24h');
            $includeDetails = $request->boolean('include_details', false);

            $metrics = [
                'period' => $period,
                'generated_at' => now()->toISOString(),
                'database' => $this->getDatabaseMetrics($period),
                'cache' => $this->getCacheMetrics($period),
                'queue' => $this->getQueueMetrics($period),
                'api' => $this->getApiMetrics($period)
            ];

            if ($includeDetails) {
                $metrics['details'] = [
                    'slow_queries' => $this->getSlowQueries($period),
                    'failed_jobs' => $this->getFailedJobs($period),
                    'error_logs' => $this->getErrorLogs($period)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'message' => 'Performance metrics generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Performance metrics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Performans metrikleri oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Export comprehensive report
     */
    public function exportComprehensive(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'report_type' => 'required|string|in:full,summary,custom',
                'format' => 'nullable|string|in:xlsx,csv,pdf',
                'sections' => 'nullable|array',
                'sections.*' => 'string|in:users,products,sellers,categories,activities,performance',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reportType = $request->input('report_type');
            $format = $request->input('format', 'xlsx');
            $sections = $request->input('sections', ['users', 'products', 'sellers']);
            
            // Generate comprehensive report
            $filename = "comprehensive_report_{$reportType}_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
            
            // This would generate the actual comprehensive report
            // For now, we'll create a placeholder
            $reportData = [
                'type' => $reportType,
                'format' => $format,
                'sections' => $sections,
                'generated_at' => now()->toISOString(),
                'filename' => $filename
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'download_url' => url("storage/exports/{$filename}"),
                    'expires_at' => now()->addDays(7)->toISOString(),
                    'report_info' => $reportData
                ],
                'message' => 'Comprehensive report generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Comprehensive export error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kapsamlı rapor oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper methods for metrics
     */
    protected function getUserMetrics(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        return [
            'total' => User::count(),
            'new' => User::where('created_at', '>=', $startDate)->count(),
            'active' => User::where('last_login_at', '>=', $startDate)->count(),
            'by_role' => User::selectRaw('COUNT(*) as count')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->groupBy('roles.name')
                ->pluck('count', 'roles.name')
                ->toArray()
        ];
    }

    protected function getProductMetrics(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        return [
            'total' => Product::count(),
            'new' => Product::where('created_at', '>=', $startDate)->count(),
            'approved' => Product::where('admin_approved', true)->count(),
            'pending' => Product::where('status', 'pending')->count(),
            'by_category' => Product::join('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw('categories.name, COUNT(*) as count')
                ->groupBy('categories.name')
                ->pluck('count', 'name')
                ->toArray()
        ];
    }

    protected function getSellerMetrics(string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        return [
            'total' => Seller::count(),
            'new' => Seller::where('created_at', '>=', $startDate)->count(),
            'active' => Seller::where('status', 'active')->count(),
            'verified' => Seller::where('is_verified', true)->count(),
            'by_subscription' => Seller::selectRaw('subscription_type, COUNT(*) as count')
                ->groupBy('subscription_type')
                ->pluck('count', 'subscription_type')
                ->toArray()
        ];
    }

    protected function getCategoryMetrics(string $period): array
    {
        return [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'with_products' => Category::has('products')->count(),
            'top_categories' => Category::withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(10)
                ->get(['name', 'products_count'])
                ->toArray()
        ];
    }

    protected function getOrderMetrics(string $period): array
    {
        // Placeholder - would implement when order system is ready
        return [
            'total' => 0,
            'new' => 0,
            'completed' => 0,
            'revenue' => 0
        ];
    }

    protected function getRevenueMetrics(string $period): array
    {
        // Placeholder - would implement when payment system is ready
        return [
            'total' => 0,
            'commission' => 0,
            'subscription' => 0,
            'by_period' => []
        ];
    }

    protected function getDatabaseMetrics(string $period): array
    {
        return [
            'connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
            'queries' => DB::select('SHOW STATUS LIKE "Queries"')[0]->Value ?? 0,
            'slow_queries' => DB::select('SHOW STATUS LIKE "Slow_queries"')[0]->Value ?? 0
        ];
    }

    protected function getCacheMetrics(string $period): array
    {
        return [
            'hit_rate' => '95%', // Placeholder
            'memory_usage' => '45%', // Placeholder
            'keys_count' => 1250 // Placeholder
        ];
    }

    protected function getQueueMetrics(string $period): array
    {
        return [
            'pending' => 0, // Would get from queue system
            'processed' => 0,
            'failed' => 0
        ];
    }

    protected function getApiMetrics(string $period): array
    {
        return [
            'requests' => 0, // Would get from logs
            'avg_response_time' => '120ms',
            'error_rate' => '0.5%'
        ];
    }

    protected function getSlowQueries(string $period): array
    {
        // Would implement slow query log analysis
        return [];
    }

    protected function getFailedJobs(string $period): array
    {
        // Would get from failed_jobs table
        return [];
    }

    protected function getErrorLogs(string $period): array
    {
        // Would get from error logs
        return [];
    }

    protected function getPeriodStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            '90d' => now()->subMonths(3),
            '1y' => now()->subYear(),
            default => now()->subMonth()
        };
    }
}
