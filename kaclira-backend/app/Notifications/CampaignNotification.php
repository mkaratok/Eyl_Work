<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $campaign;
    protected $discountPercentage;
    protected $validUntil;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $campaign)
    {
        $this->campaign = $campaign;
        $this->discountPercentage = $campaign['discount_percentage'] ?? 0;
        $this->validUntil = $campaign['valid_until'] ?? null;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        $preferences = $notifiable->notification_preferences ?? [];
        
        if ($preferences['email_campaigns'] ?? true) {
            $channels[] = 'mail';
        }
        
        if ($preferences['push_campaigns'] ?? true) {
            $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->campaign['title'])
            ->greeting('Merhaba ' . $notifiable->name . '!')
            ->line($this->campaign['description']);

        if ($this->discountPercentage > 0) {
            $message->line('İndirim Oranı: %' . $this->discountPercentage);
        }

        if ($this->validUntil) {
            $message->line('Geçerlilik: ' . \Carbon\Carbon::parse($this->validUntil)->format('d.m.Y H:i') . ' tarihine kadar');
        }

        $message->action('Kampanyayı İncele', $this->campaign['action_url'] ?? url('/campaigns'))
            ->line('Bu fırsatı kaçırmayın!');

        return $message;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'campaign',
            'title' => $this->campaign['title'],
            'message' => $this->campaign['description'],
            'campaign_id' => $this->campaign['id'] ?? null,
            'discount_percentage' => $this->discountPercentage,
            'valid_until' => $this->validUntil,
            'action_url' => $this->campaign['action_url'] ?? '/campaigns',
            'image_url' => $this->campaign['image_url'] ?? null,
            'icon' => 'campaign',
            'color' => 'purple'
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'title' => $this->campaign['title'],
            'body' => $this->campaign['description'],
            'icon' => '/images/campaign-icon.png',
            'badge' => '/images/badge-icon.png',
            'data' => [
                'campaign_id' => $this->campaign['id'] ?? null,
                'action_url' => $this->campaign['action_url'] ?? '/campaigns',
                'type' => 'campaign'
            ]
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend($notifiable, $channel): bool
    {
        // Check user's campaign preferences
        $preferences = $notifiable->notification_preferences ?? [];
        
        // Don't send if user opted out of campaigns
        if (!($preferences['receive_campaigns'] ?? true)) {
            return false;
        }

        // Check if user is in target audience
        if (isset($this->campaign['target_audience'])) {
            $targetAudience = $this->campaign['target_audience'];
            
            // Check user segments
            if (isset($targetAudience['user_segments'])) {
                $userSegments = $notifiable->segments ?? [];
                $hasMatchingSegment = !empty(array_intersect($targetAudience['user_segments'], $userSegments));
                
                if (!$hasMatchingSegment) {
                    return false;
                }
            }
            
            // Check location
            if (isset($targetAudience['locations'])) {
                $userLocation = $notifiable->location ?? null;
                if ($userLocation && !in_array($userLocation, $targetAudience['locations'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the notification's delivery delay.
     */
    public function withDelay($notifiable): \DateTimeInterface|\DateInterval|int|null
    {
        // Send campaigns immediately for better engagement
        return null;
    }
}
