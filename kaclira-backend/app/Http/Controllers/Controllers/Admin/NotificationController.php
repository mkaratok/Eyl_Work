<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Models\User;
use App\Notifications\CampaignNotification;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware(['auth:api', 'role:admin']);
    }

    /**
     * Get notification logs and statistics
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'integer|min:1|max:100',
                'type' => 'string|in:price_drop,stock_available,campaign,price_alert,order,system',
                'date_from' => 'date',
                'date_to' => 'date|after_or_equal:date_from',
                'user_id' => 'integer|exists:users,id',
                'page' => 'integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $limit = $request->get('limit', 20);
            $type = $request->get('type');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $userId = $request->get('user_id');
            $page = $request->get('page', 1);

            // Get notifications from database
            $query = DB::table('notifications');

            // Apply filters
            if ($type) {
                $query->where('data->type', $type);
            }

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
            }

            if ($userId) {
                $query->where('notifiable_id', $userId);
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $notifications = $query->paginate($limit, ['*'], 'page', $page);

            // Get statistics
            $stats = $this->getNotificationStats($dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'meta' => [
                    'total' => $notifications->total(),
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'last_page' => $notifications->lastPage()
                ],
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send campaign notification to users
     */
    public function sendCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'discount_percentage' => 'numeric|min:0|max:100',
                'valid_until' => 'date|after:now',
                'action_url' => 'url',
                'image_url' => 'url',
                'target_audience' => 'array',
                'target_audience.user_segments' => 'array',
                'target_audience.locations' => 'array',
                'target_audience.registered_after' => 'date',
                'target_audience.active_since' => 'date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $campaign = [
                'id' => uniqid('campaign_'),
                'title' => $request->title,
                'description' => $request->description,
                'discount_percentage' => $request->discount_percentage,
                'valid_until' => $request->valid_until,
                'action_url' => $request->action_url,
                'image_url' => $request->image_url,
                'target_audience' => $request->target_audience ?? []
            ];

            $targetCriteria = $request->target_audience ?? [];

            $this->notificationService->sendCampaignNotification($campaign, $targetCriteria);

            return response()->json([
                'success' => true,
                'message' => 'Campaign notification sent',
                'data' => $campaign
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send campaign notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'date',
                'date_to' => 'date|after_or_equal:date_from',
                'period' => 'string|in:day,week,month'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));
            $period = $request->get('period', 'day');

            $stats = $this->getNotificationStats($dateFrom, $dateTo);
            $chartData = $this->getNotificationChartData($dateFrom, $dateTo, $period);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'chart_data' => $chartData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user notification preferences summary
     */
    public function getUserPreferencesSummary(Request $request): JsonResponse
    {
        try {
            $summary = DB::table('users')
                ->selectRaw("
                    COUNT(*) as total_users,
                    SUM(CASE WHEN JSON_EXTRACT(notification_preferences, '$.email_price_alerts') = true THEN 1 ELSE 0 END) as email_price_alerts_enabled,
                    SUM(CASE WHEN JSON_EXTRACT(notification_preferences, '$.push_price_alerts') = true THEN 1 ELSE 0 END) as push_price_alerts_enabled,
                    SUM(CASE WHEN JSON_EXTRACT(notification_preferences, '$.email_campaigns') = true THEN 1 ELSE 0 END) as email_campaigns_enabled,
                    SUM(CASE WHEN JSON_EXTRACT(notification_preferences, '$.push_campaigns') = true THEN 1 ELSE 0 END) as push_campaigns_enabled,
                    SUM(CASE WHEN JSON_EXTRACT(notification_preferences, '$.receive_campaigns') = true THEN 1 ELSE 0 END) as campaigns_enabled
                ")
                ->first();

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user preferences summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification system
     */
    public function testNotification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'type' => 'required|string|in:push,email,campaign',
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);

            switch ($request->type) {
                case 'push':
                    $this->notificationService->sendPushNotification($user, [
                        'title' => $request->title,
                        'body' => $request->message,
                        'data' => ['test' => true, 'admin_sent' => true]
                    ]);
                    break;

                case 'campaign':
                    $campaign = [
                        'id' => 'test_campaign_' . time(),
                        'title' => $request->title,
                        'description' => $request->message,
                        'action_url' => url('/')
                    ];
                    $user->notify(new CampaignNotification($campaign));
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent to user'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    protected function getNotificationStats(string $dateFrom = null, string $dateTo = null): array
    {
        $query = DB::table('notifications');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $stats = $query->selectRaw("
            COUNT(*) as total_notifications,
            SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_notifications,
            SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread_notifications,
            SUM(CASE WHEN JSON_EXTRACT(data, '$.type') = 'price_drop' THEN 1 ELSE 0 END) as price_drop_notifications,
            SUM(CASE WHEN JSON_EXTRACT(data, '$.type') = 'stock_available' THEN 1 ELSE 0 END) as stock_available_notifications,
            SUM(CASE WHEN JSON_EXTRACT(data, '$.type') = 'campaign' THEN 1 ELSE 0 END) as campaign_notifications,
            SUM(CASE WHEN JSON_EXTRACT(data, '$.type') = 'price_alert' THEN 1 ELSE 0 END) as price_alert_notifications
        ")->first();

        $readRate = $stats->total_notifications > 0 
            ? round(($stats->read_notifications / $stats->total_notifications) * 100, 2)
            : 0;

        return [
            'total_notifications' => $stats->total_notifications,
            'read_notifications' => $stats->read_notifications,
            'unread_notifications' => $stats->unread_notifications,
            'read_rate' => $readRate,
            'by_type' => [
                'price_drop' => $stats->price_drop_notifications,
                'stock_available' => $stats->stock_available_notifications,
                'campaign' => $stats->campaign_notifications,
                'price_alert' => $stats->price_alert_notifications
            ]
        ];
    }

    /**
     * Get notification chart data
     */
    protected function getNotificationChartData(string $dateFrom, string $dateTo, string $period): array
    {
        $dateFormat = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $notifications = DB::table('notifications')
            ->selectRaw("
                DATE_FORMAT(created_at, '{$dateFormat}') as period,
                COUNT(*) as total,
                SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count
            ")
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo . ' 23:59:59')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'labels' => $notifications->pluck('period')->toArray(),
            'datasets' => [
                [
                    'label' => 'Total Notifications',
                    'data' => $notifications->pluck('total')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2
                ],
                [
                    'label' => 'Read Notifications',
                    'data' => $notifications->pluck('read_count')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2
                ]
            ]
        ];
    }
}
