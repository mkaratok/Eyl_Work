<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validator = Validator::make($request->all(), [
                'limit' => 'integer|min:1|max:100',
                'unread_only' => 'boolean',
                'type' => 'string|in:price_drop,stock_available,campaign,price_alert,order,system',
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
            $unreadOnly = $request->boolean('unread_only');
            $type = $request->get('type');
            $page = $request->get('page', 1);

            $query = $user->notifications();

            // Filter by read status
            if ($unreadOnly) {
                $query->whereNull('read_at');
            }

            // Filter by type
            if ($type) {
                $query->where('data->type', $type);
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $notifications = $query->paginate($limit, ['*'], 'page', $page);

            // Get unread count
            $unreadCount = $user->unreadNotifications()->count();

            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'meta' => [
                    'total' => $notifications->total(),
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'last_page' => $notifications->lastPage(),
                    'unread_count' => $unreadCount
                ]
            ]);

        } catch (\Exception $e) {
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
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = $request->user();
            
            $notification = $user->notifications()->find($notificationId);
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
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
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $user->unreadNotifications->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = $request->user();
            
            $notification = $user->notifications()->find($notificationId);
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $preferences = $this->notificationService->getUserPreferences($user);

            return response()->json([
                'success' => true,
                'data' => $preferences
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_price_alerts' => 'boolean',
                'email_stock_alerts' => 'boolean',
                'email_campaigns' => 'boolean',
                'email_order_updates' => 'boolean',
                'push_price_alerts' => 'boolean',
                'push_stock_alerts' => 'boolean',
                'push_campaigns' => 'boolean',
                'push_order_updates' => 'boolean',
                'receive_campaigns' => 'boolean',
                'frequency' => 'string|in:immediate,daily,weekly',
                'quiet_hours_start' => 'string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'quiet_hours_end' => 'string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $preferences = $request->only([
                'email_price_alerts',
                'email_stock_alerts',
                'email_campaigns',
                'email_order_updates',
                'push_price_alerts',
                'push_stock_alerts',
                'push_campaigns',
                'push_order_updates',
                'receive_campaigns',
                'frequency',
                'quiet_hours_start',
                'quiet_hours_end'
            ]);

            $success = $this->notificationService->updateUserPreferences($user, $preferences);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update preferences'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated',
                'data' => $this->notificationService->getUserPreferences($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register push notification token
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'platform' => 'required|string|in:ios,android,web',
                'device_id' => 'string|max:255',
                'endpoint' => 'string|max:500',
                'p256dh' => 'string|max:255',
                'auth' => 'string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Deactivate existing tokens for this device
            if ($request->has('device_id')) {
                $user->pushNotificationTokens()
                    ->where('device_id', $request->device_id)
                    ->update(['is_active' => false]);
            }

            // Create new token record
            $tokenData = [
                'token' => $request->token,
                'platform' => $request->platform,
                'device_id' => $request->device_id,
                'is_active' => true
            ];

            // Add web push specific fields
            if ($request->platform === 'web') {
                $tokenData['endpoint'] = $request->endpoint;
                $tokenData['p256dh'] = $request->p256dh;
                $tokenData['auth'] = $request->auth;
            }

            $user->pushNotificationTokens()->create($tokenData);

            return response()->json([
                'success' => true,
                'message' => 'Push notification token registered'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register push token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unregister push notification token
     */
    public function unregisterPushToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            $user->pushNotificationTokens()
                ->where('token', $request->token)
                ->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Push notification token unregistered'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unregister push token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification (for development)
     */
    public function testNotification(Request $request): JsonResponse
    {
        if (!app()->environment('local', 'staging')) {
            return response()->json([
                'success' => false,
                'message' => 'Test notifications only available in development'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:push,email',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            if ($request->type === 'push') {
                $this->notificationService->sendPushNotification($user, [
                    'title' => $request->title,
                    'body' => $request->body,
                    'data' => ['test' => true]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
