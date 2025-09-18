<?php

namespace App\Notifications;

use App\Models\ProductPrice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PriceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ProductPrice $productPrice;
    protected ?float $oldPrice;
    protected float $newPrice;
    protected float $priceDecrease;
    protected float $decreasePercentage;
    protected bool $isAdminAlert;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        ProductPrice $productPrice,
        ?float $oldPrice,
        float $newPrice,
        float $priceDecrease,
        float $decreasePercentage,
        bool $isAdminAlert = false
    ) {
        $this->productPrice = $productPrice;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
        $this->priceDecrease = $priceDecrease;
        $this->decreasePercentage = $decreasePercentage;
        $this->isAdminAlert = $isAdminAlert;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Send email for significant price drops or admin alerts
        if ($this->decreasePercentage >= 15 || $this->isAdminAlert) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $product = $this->productPrice->product;
        $seller = $this->productPrice->seller;
        
        $subject = $this->isAdminAlert 
            ? 'Significant Price Drop Alert - Admin'
            : 'Price Drop Alert - ' . ($product->name ?? 'Product');

        $greeting = $this->isAdminAlert 
            ? 'Admin Alert'
            : 'Great news!';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($this->getEmailMessage())
            ->line('Product: ' . ($product->name ?? 'Unknown'))
            ->line('Brand: ' . ($product->brand ?? 'Unknown'))
            ->line('Seller: ' . ($seller->name ?? 'Unknown'))
            ->line('Old Price: ₺' . number_format($this->oldPrice, 2))
            ->line('New Price: ₺' . number_format($this->newPrice, 2))
            ->line('You Save: ₺' . number_format($this->priceDecrease, 2) . ' (' . round($this->decreasePercentage, 1) . '%)')
            ->action('View Product', $this->getProductUrl())
            ->line('Don\'t miss this opportunity!');

        if ($this->isAdminAlert) {
            $message->line('This is an admin notification for monitoring significant price changes.');
        }

        return $message;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $product = $this->productPrice->product;
        $seller = $this->productPrice->seller;

        return [
            'type' => 'price_alert',
            'product_id' => $this->productPrice->product_id,
            'product_name' => $product->name ?? 'Unknown',
            'product_brand' => $product->brand ?? 'Unknown',
            'seller_id' => $this->productPrice->seller_id,
            'seller_name' => $seller->name ?? 'Unknown',
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
            'price_decrease' => $this->priceDecrease,
            'decrease_percentage' => round($this->decreasePercentage, 2),
            'is_admin_alert' => $this->isAdminAlert,
            'product_url' => $this->getProductUrl(),
            'message' => $this->getDatabaseMessage()
        ];
    }

    /**
     * Get email message text
     */
    private function getEmailMessage(): string
    {
        if ($this->isAdminAlert) {
            return "A significant price drop of {$this->decreasePercentage}% has been detected and requires admin attention.";
        }

        return "The price of a product in your favorites has dropped by {$this->decreasePercentage}%!";
    }

    /**
     * Get database message text
     */
    private function getDatabaseMessage(): string
    {
        $product = $this->productPrice->product;
        $productName = $product->name ?? 'Unknown Product';
        
        if ($this->isAdminAlert) {
            return "Admin Alert: {$productName} price dropped by ₺{$this->priceDecrease} ({$this->decreasePercentage}%)";
        }

        return "{$productName} price dropped by ₺{$this->priceDecrease} ({$this->decreasePercentage}%) - Now ₺{$this->newPrice}";
    }

    /**
     * Get product URL
     */
    private function getProductUrl(): string
    {
        $product = $this->productPrice->product;
        $baseUrl = config('app.frontend_url', 'http://localhost:3000');
        
        if ($product && $product->slug) {
            return "{$baseUrl}/products/{$product->slug}";
        }
        
        return "{$baseUrl}/products/{$this->productPrice->product_id}";
    }
}
