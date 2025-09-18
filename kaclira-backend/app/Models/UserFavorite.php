<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'price_alert_enabled',
        'target_price',
    ];

    protected function casts(): array
    {
        return [
            'price_alert_enabled' => 'boolean',
            'target_price' => 'decimal:2',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeWithPriceAlert($query)
    {
        return $query->where('price_alert_enabled', true)
                    ->whereNotNull('target_price');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Methods
    public function enablePriceAlert($targetPrice = null)
    {
        $this->update([
            'price_alert_enabled' => true,
            'target_price' => $targetPrice,
        ]);
    }

    public function disablePriceAlert()
    {
        $this->update([
            'price_alert_enabled' => false,
            'target_price' => null,
        ]);
    }

    // Check if current minimum price is below target
    public function shouldTriggerAlert()
    {
        if (!$this->price_alert_enabled || !$this->target_price) {
            return false;
        }

        $minPrice = $this->product->min_price;
        return $minPrice && $minPrice <= $this->target_price;
    }
}
