<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\Product;
use App\Models\ProductPrice;

class PriceDropNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;
    protected $oldPrice;
    protected $newPrice;
    protected $dropPercentage;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product, float $oldPrice, float $newPrice)
    {
        $this->product = $product;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
        $this->dropPercentage = round((($oldPrice - $newPrice) / $oldPrice) * 100, 1);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Check user preferences
        $preferences = $notifiable->notification_preferences ?? [];
        
        if ($preferences['email_price_alerts'] ?? true) {
            $channels[] = 'mail';
        }
        
        if ($preferences['push_price_alerts'] ?? true) {
            $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Fiyat Düştü! ' . $this->product->name)
            ->greeting('Merhaba ' . $notifiable->name . '!')
            ->line($this->product->name . ' ürününde fiyat düşüşü var!')
            ->line('Eski Fiyat: ₺' . number_format($this->oldPrice, 2))
            ->line('Yeni Fiyat: ₺' . number_format($this->newPrice, 2))
            ->line('İndirim Oranı: %' . $this->dropPercentage)
            ->action('Ürünü İncele', url('/products/' . $this->product->id))
            ->line('Bu fırsatı kaçırmayın!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'price_drop',
            'title' => 'Fiyat Düştü!',
            'message' => $this->product->name . ' ürününde %' . $this->dropPercentage . ' indirim',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_image' => $this->product->image_url,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
            'drop_percentage' => $this->dropPercentage,
            'action_url' => '/products/' . $this->product->id,
            'icon' => 'price-down',
            'color' => 'green'
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'title' => 'Fiyat Düştü!',
            'body' => $this->product->name . ' ürününde %' . $this->dropPercentage . ' indirim',
            'icon' => '/images/price-drop-icon.png',
            'badge' => '/images/badge-icon.png',
            'data' => [
                'product_id' => $this->product->id,
                'action_url' => '/products/' . $this->product->id,
                'type' => 'price_drop'
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
        // Don't send if price drop is less than 5%
        if ($this->dropPercentage < 5) {
            return false;
        }

        // Check if user has this product in favorites or price alerts
        $hasFavorite = $notifiable->favorites()
            ->where('product_id', $this->product->id)
            ->exists();

        $hasPriceAlert = $notifiable->priceAlerts()
            ->where('product_id', $this->product->id)
            ->where('target_price', '>=', $this->newPrice)
            ->exists();

        return $hasFavorite || $hasPriceAlert;
    }

    /**
     * Get the notification's delivery delay.
     */
    public function withDelay($notifiable): \DateTimeInterface|\DateInterval|int|null
    {
        // Delay email notifications by 5 minutes to batch them
        return now()->addMinutes(5);
    }
}
