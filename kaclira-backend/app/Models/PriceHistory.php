<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    protected $table = 'price_history';

    protected $fillable = [
        'product_price_id',
        'old_price',
        'new_price',
        'old_stock',
        'new_stock',
        'change_type',
    ];

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'old_stock' => 'integer',
            'new_stock' => 'integer',
        ];
    }

    // Relationships
    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }

    // Scopes
    public function scopeByChangeType($query, $type)
    {
        return $query->where('change_type', $type);
    }

    public function scopePriceChanges($query)
    {
        return $query->whereIn('change_type', ['price_increase', 'price_decrease', 'both']);
    }

    public function scopeStockChanges($query)
    {
        return $query->whereIn('change_type', ['stock_change', 'both']);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getPriceChangeAmountAttribute()
    {
        return $this->new_price - $this->old_price;
    }

    public function getPriceChangePercentageAttribute()
    {
        if ($this->old_price == 0) return 0;
        return (($this->new_price - $this->old_price) / $this->old_price) * 100;
    }

    public function getStockChangeAmountAttribute()
    {
        return $this->new_stock - $this->old_stock;
    }
}
