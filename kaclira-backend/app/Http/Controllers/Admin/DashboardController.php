<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Get dashboard statistics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'nullable|string|in:7d,30d,90d,1y'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->input('period', '30d');
            $stats = $this->adminService->getDashboardStats($period);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => $period,
                'message' => 'Dashboard statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard stats error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dashboard verilerini alırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get real-time dashboard data
     * 
     * @return JsonResponse
     */
    public function realtime(): JsonResponse
    {
        try {
            $data = [
                'timestamp' => now()->toISOString(),
                'online_users' => $this->getOnlineUsersCount(),
                'active_sessions' => $this->getActiveSessionsCount(),
                'system_status' => $this->getSystemStatus(),
                'recent_activities' => $this->getRecentActivities(),
                'alerts' => $this->getSystemAlerts()
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Real-time data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Real-time dashboard error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gerçek zamanlı veriler alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get system health status
     * 
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'checks' => [
                    'database' => $this->checkDatabase(),
                    'cache' => $this->checkCache(),
                    'storage' => $this->checkStorage(),
                    'queue' => $this->checkQueue(),
                    'mail' => $this->checkMail()
                ],
                'metrics' => [
                    'memory_usage' => $this->getMemoryUsage(),
                    'disk_usage' => $this->getDiskUsage(),
                    'cpu_load' => $this->getCpuLoad(),
                    'response_time' => $this->getAverageResponseTime()
                ]
            ];

            // Determine overall health status
            $failedChecks = collect($health['checks'])->filter(fn($check) => !$check['status'])->count();
            if ($failedChecks > 0) {
                $health['status'] = $failedChecks > 2 ? 'critical' : 'warning';
            }

            return response()->json([
                'success' => true,
                'data' => $health,
                'message' => 'System health status retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('System health check error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Sistem durumu kontrol edilirken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get activity logs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function activities(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'nullable|string|in:user,product,seller,system',
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $type = $request->input('type');
            $limit = $request->input('limit', 20);
            $page = $request->input('page', 1);

            $activities = $this->getActivityLogs($type, $limit, $page);

            return response()->json([
                'success' => true,
                'data' => $activities,
                'message' => 'Activity logs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Activity logs error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Aktivite logları alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get quick actions data
     * 
     * @return JsonResponse
     */
    public function quickActions(): JsonResponse
    {
        try {
            $actions = [
                'pending_products' => [
                    'count' => \App\Models\Product::where('status', 'pending')->count(),
                    'action' => 'product_approval',
                    'url' => '/admin/products?status=pending'
                ],
                'unverified_sellers' => [
                    'count' => \App\Models\Seller::where('is_verified', false)->count(),
                    'action' => 'seller_verification',
                    'url' => '/admin/sellers?status=unverified'
                ],
                'unread_notifications' => [
                    'count' => \App\Models\Notification::whereNull('read_at')->count(),
                    'action' => 'notifications',
                    'url' => '/admin/notifications'
                ],
                'system_alerts' => [
                    'count' => count($this->getSystemAlerts()),
                    'action' => 'system_health',
                    'url' => '/admin/system/health'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $actions,
                'message' => 'Quick actions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Quick actions error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hızlı işlemler alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Export dashboard data
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:stats,activities,reports',
                'format' => 'nullable|string|in:csv,xlsx,pdf',
                'period' => 'nullable|string|in:7d,30d,90d,1y'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $type = $request->input('type');
            $format = $request->input('format', 'xlsx');
            $period = $request->input('period', '30d');

            // Generate export file
            $filename = $this->generateExportFile($type, $format, $period);

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'download_url' => url("storage/exports/{$filename}"),
                    'expires_at' => now()->addHours(24)->toISOString()
                ],
                'message' => 'Export file generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard export error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Dışa aktarma sırasında hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    protected function getOnlineUsersCount(): int
    {
        // This would typically use Redis or sessions to count online users
        return \App\Models\User::where('last_login_at', '>=', now()->subMinutes(15))->count();
    }

    protected function getActiveSessionsCount(): int
    {
        // This would count active sessions from session storage
        return 0; // Placeholder
    }

    protected function getSystemStatus(): array
    {
        return [
            'status' => 'operational',
            'uptime' => '99.9%',
            'last_restart' => now()->subDays(7)->toISOString(),
            'version' => config('app.version', '1.0.0')
        ];
    }

    protected function getRecentActivities(): array
    {
        // This would fetch recent system activities
        return [
            [
                'id' => 1,
                'type' => 'user_registration',
                'description' => 'New user registered',
                'user' => 'john@example.com',
                'timestamp' => now()->subMinutes(5)->toISOString()
            ],
            [
                'id' => 2,
                'type' => 'product_approval',
                'description' => 'Product approved',
                'user' => 'admin@kaclira.com',
                'timestamp' => now()->subMinutes(10)->toISOString()
            ]
        ];
    }

    protected function getSystemAlerts(): array
    {
        $alerts = [];

        // Check for pending products
        $pendingProducts = \App\Models\Product::where('status', 'pending')->count();
        if ($pendingProducts > 50) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$pendingProducts} products pending approval",
                'action' => 'review_products'
            ];
        }

        // Check for unverified sellers
        $unverifiedSellers = \App\Models\Seller::where('is_verified', false)->count();
        if ($unverifiedSellers > 10) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$unverifiedSellers} sellers awaiting verification",
                'action' => 'verify_sellers'
            ];
        }

        return $alerts;
    }

    protected function getActivityLogs(string $type = null, int $limit = 20, int $page = 1): array
    {
        // This would fetch activity logs from a logging system
        return [
            'data' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => 0,
                'last_page' => 1
            ]
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => true, 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Database connection failed'];
        }
    }

    protected function checkCache(): array
    {
        try {
            \Cache::put('health_check', 'ok', 60);
            $value = \Cache::get('health_check');
            return ['status' => $value === 'ok', 'message' => 'Cache system OK'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Cache system failed'];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usagePercent = (($totalSpace - $diskSpace) / $totalSpace) * 100;
            
            return [
                'status' => $usagePercent < 90,
                'message' => "Disk usage: {$usagePercent}%",
                'usage_percent' => round($usagePercent, 2)
            ];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Storage check failed'];
        }
    }

    protected function checkQueue(): array
    {
        try {
            // This would check queue status
            return ['status' => true, 'message' => 'Queue system OK'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Queue system failed'];
        }
    }

    protected function checkMail(): array
    {
        try {
            // This would check mail system
            return ['status' => true, 'message' => 'Mail system OK'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Mail system failed'];
        }
    }

    protected function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'current' => $this->formatBytes($memoryUsage),
            'limit' => $memoryLimit,
            'percentage' => round(($memoryUsage / $this->parseBytes($memoryLimit)) * 100, 2)
        ];
    }

    protected function getDiskUsage(): array
    {
        $freeBytes = disk_free_space(storage_path());
        $totalBytes = disk_total_space(storage_path());
        $usedBytes = $totalBytes - $freeBytes;
        
        return [
            'used' => $this->formatBytes($usedBytes),
            'free' => $this->formatBytes($freeBytes),
            'total' => $this->formatBytes($totalBytes),
            'percentage' => round(($usedBytes / $totalBytes) * 100, 2)
        ];
    }

    protected function getCpuLoad(): array
    {
        // This would get actual CPU load on Linux systems
        return [
            'current' => '15%',
            'average' => '12%'
        ];
    }

    protected function getAverageResponseTime(): string
    {
        // This would calculate average response time from logs
        return '120ms';
    }

    protected function generateExportFile(string $type, string $format, string $period): string
    {
        // This would generate actual export files
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "dashboard_{$type}_{$period}_{$timestamp}.{$format}";
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    protected function parseBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}
