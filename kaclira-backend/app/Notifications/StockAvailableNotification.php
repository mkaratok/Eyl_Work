<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Product;

class StockAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;
    protected $stockQuantity;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product, int $stockQuantity)
    {
        $this->product = $product;
        $this->stockQuantity = $stockQuantity;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        $preferences = $notifiable->notification_preferences ?? [];
        
        if ($preferences['email_stock_alerts'] ?? true) {
            $channels[] = 'mail';
        }
        
        if ($preferences['push_stock_alerts'] ?? true) {
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
            ->subject('Stokta! ' . $this->product->name)
            ->greeting('Merhaba ' . $notifiable->name . '!')
            ->line($this->product->name . ' artık stokta!')
            ->line('Mevcut Stok: ' . $this->stockQuantity . ' adet')
            ->action('Hemen Satın Al', url('/products/' . $this->product->id))
            ->line('Stoklar tükenmeden önce sipariş verin!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'stock_available',
            'title' => 'Stokta!',
            'message' => $this->product->name . ' artık stokta (' . $this->stockQuantity . ' adet)',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_image' => $this->product->image_url,
            'stock_quantity' => $this->stockQuantity,
            'action_url' => '/products/' . $this->product->id,
            'icon' => 'stock-available',
            'color' => 'blue'
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'title' => 'Stokta!',
            'body' => $this->product->name . ' artık stokta (' . $this->stockQuantity . ' adet)',
            'icon' => '/images/stock-icon.png',
            'badge' => '/images/badge-icon.png',
            'data' => [
                'product_id' => $this->product->id,
                'action_url' => '/products/' . $this->product->id,
                'type' => 'stock_available'
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
        // Check if user has this product in favorites or stock alerts
        $hasFavorite = $notifiable->favorites()
            ->where('product_id', $this->product->id)
            ->exists();

        $hasStockAlert = $notifiable->stockAlerts()
            ->where('product_id', $this->product->id)
            ->exists();

        return $hasFavorite || $hasStockAlert;
    }
}
