<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'seller_id',
        'price',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeLowestPrice($query)
    {
        return $query->orderBy('price', 'asc');
    }

    public function scopeHighestPrice($query)
    {
        return $query->orderBy('price', 'desc');
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' TL';
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock > 0;
    }
}
