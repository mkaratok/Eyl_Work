<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $data;
    protected $retryCount = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $data)
    {
        $this->user = $user;
        $this->data = $data;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get user's push notification tokens
            $pushTokens = $this->user->pushNotificationTokens()
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            if (empty($pushTokens)) {
                Log::info('No push tokens found for user', ['user_id' => $this->user->id]);
                return;
            }

            // Send to Firebase Cloud Messaging (FCM)
            $this->sendFCMNotification($pushTokens);

            // Send to Apple Push Notification Service (APNS) if iOS tokens exist
            $iosTokens = $this->user->pushNotificationTokens()
                ->where('platform', 'ios')
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            if (!empty($iosTokens)) {
                $this->sendAPNSNotification($iosTokens);
            }

            // Send Web Push Notification
            $webTokens = $this->user->pushNotificationTokens()
                ->where('platform', 'web')
                ->where('is_active', true)
                ->get();

            if ($webTokens->isNotEmpty()) {
                $this->sendWebPushNotification($webTokens);
            }

            Log::info('Push notification sent successfully', [
                'user_id' => $this->user->id,
                'tokens_count' => count($pushTokens)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'data' => $this->data
            ]);

            // Retry the job if it hasn't exceeded retry limit
            if ($this->attempts() < $this->retryCount) {
                $this->release(60); // Retry after 1 minute
            }
        }
    }

    /**
     * Send notification via Firebase Cloud Messaging
     */
    protected function sendFCMNotification(array $tokens): void
    {
        $fcmServerKey = config('services.fcm.server_key');
        
        if (!$fcmServerKey) {
            Log::warning('FCM server key not configured');
            return;
        }

        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $this->data['title'],
                'body' => $this->data['body'],
                'icon' => $this->data['icon'] ?? '/images/notification-icon.png',
                'badge' => $this->data['badge'] ?? '/images/badge-icon.png',
                'sound' => 'default',
                'click_action' => $this->data['action_url'] ?? '/'
            ],
            'data' => array_merge($this->data['data'] ?? [], [
                'click_action' => $this->data['action_url'] ?? '/'
            ]),
            'android' => [
                'notification' => [
                    'channel_id' => 'kaclira_notifications',
                    'priority' => 'high',
                    'default_sound' => true,
                    'default_vibrate_timings' => true
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $this->data['title'],
                            'body' => $this->data['body']
                        ],
                        'badge' => 1,
                        'sound' => 'default'
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $fcmServerKey,
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if (!$response->successful()) {
            throw new \Exception('FCM request failed: ' . $response->body());
        }

        $result = $response->json();
        
        // Handle invalid tokens
        if (isset($result['results'])) {
            $this->handleInvalidTokens($tokens, $result['results']);
        }

        Log::info('FCM notification sent', [
            'success' => $result['success'] ?? 0,
            'failure' => $result['failure'] ?? 0
        ]);
    }

    /**
     * Send notification via Apple Push Notification Service
     */
    protected function sendAPNSNotification(array $tokens): void
    {
        $apnsKeyPath = config('services.apns.key_path');
        $apnsKeyId = config('services.apns.key_id');
        $apnsTeamId = config('services.apns.team_id');
        $apnsBundleId = config('services.apns.bundle_id');

        if (!$apnsKeyPath || !$apnsKeyId || !$apnsTeamId || !$apnsBundleId) {
            Log::warning('APNS configuration incomplete');
            return;
        }

        // Implementation for APNS would go here
        // This is a simplified version - in production, use a proper APNS library
        Log::info('APNS notification would be sent here', [
            'tokens_count' => count($tokens)
        ]);
    }

    /**
     * Send Web Push Notification
     */
    protected function sendWebPushNotification($webTokens): void
    {
        $vapidPublicKey = config('services.vapid.public_key');
        $vapidPrivateKey = config('services.vapid.private_key');
        $vapidSubject = config('services.vapid.subject');

        if (!$vapidPublicKey || !$vapidPrivateKey || !$vapidSubject) {
            Log::warning('VAPID configuration incomplete');
            return;
        }

        foreach ($webTokens as $token) {
            try {
                $payload = json_encode([
                    'title' => $this->data['title'],
                    'body' => $this->data['body'],
                    'icon' => $this->data['icon'] ?? '/images/notification-icon.png',
                    'badge' => $this->data['badge'] ?? '/images/badge-icon.png',
                    'data' => $this->data['data'] ?? [],
                    'actions' => [
                        [
                            'action' => 'view',
                            'title' => 'Görüntüle'
                        ]
                    ]
                ]);

                // Use a Web Push library like minishlink/web-push
                // This is a placeholder for the actual implementation
                Log::info('Web push notification sent', [
                    'endpoint' => substr($token->endpoint, 0, 50) . '...'
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send web push notification', [
                    'token_id' => $token->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle invalid FCM tokens
     */
    protected function handleInvalidTokens(array $tokens, array $results): void
    {
        foreach ($results as $index => $result) {
            if (isset($result['error'])) {
                $token = $tokens[$index];
                $error = $result['error'];

                // Deactivate invalid tokens
                if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                    $this->user->pushNotificationTokens()
                        ->where('token', $token)
                        ->update(['is_active' => false]);

                    Log::info('Deactivated invalid push token', [
                        'user_id' => $this->user->id,
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $error
                    ]);
                }
            }
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
            'data' => $this->data
        ]);
    }
}
