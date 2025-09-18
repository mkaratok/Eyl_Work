<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'barcode',
        'name',
        'slug',
        'description',
        'short_description',
        'category_id',
        'brand',
        'model',
        'sku',
        'specifications',
        'images',
        'thumbnail',
        'weight',
        'dimensions',
        'status',
        'admin_approved',
        'approval_notes',
        'meta_title',
        'meta_description',
        'tags',
        'is_featured',
        'sort_order',
        'created_by',
        // Import-related fields
        'gtin',
        'mpn',
        'availability',
        'condition',
        'additional_images',
        'external_url',
        'google_category',
        'import_source',
        'import_reference_id',
        'last_imported_at',
        // Price-related fields
        'seller_id',
        'price',
        'sale_price',
        'image_url',
        'is_active',
        'stock_quantity', // Add this missing field
    ];

    protected $appends = [
        'thumbnail_url',
        'image_urls',
        'main_image',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'images' => 'array',
            'dimensions' => 'array',
            'tags' => 'array',
            'admin_approved' => 'boolean',
            'is_featured' => 'boolean',
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
            // Import-related field casts
            'additional_images' => 'array',
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'imported_at' => 'datetime',
            'stock_quantity' => 'integer', // Add this missing cast
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'PRD-' . strtoupper(Str::random(8));
            }
            if (empty($product->sort_order)) {
                $product->sort_order = static::max('sort_order') + 1;
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function activePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)->where('is_active', true);
    }

    public function priceHistory(): HasManyThrough
    {
        return $this->hasManyThrough(PriceHistory::class, ProductPrice::class);
    }

    public function userFavorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeApproved($query)
    {
        return $query->where('admin_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('admin_approved', false)->where('status', '!=', 'draft');
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('activePrices', function ($q) {
            $q->where('stock', '>', 0);
        });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        if (is_array($categoryId)) {
            return $query->whereIn('category_id', $categoryId);
        }
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, $brand)
    {
        if (is_array($brand)) {
            return $query->whereIn('brand', $brand);
        }
        return $query->where('brand', $brand);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereHas('activePrices', function ($q) use ($minPrice, $maxPrice) {
            if ($minPrice) {
                $q->where('price', '>=', $minPrice);
            }
            if ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            }
        });
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('short_description', 'like', "%{$term}%")
              ->orWhere('brand', 'like', "%{$term}%")
              ->orWhere('model', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('barcode', $term);
        });
    }

    public function scopeWithImages($query)
    {
        return $query->whereNotNull('images')->where('images', '!=', '[]');
    }

    public function scopeOrderByPopularity($query)
    {
        return $query->withCount('userFavorites')
                    ->orderBy('user_favorites_count', 'desc');
    }

    public function scopeOrderByPrice($query, $direction = 'asc')
    {
        return $query->leftJoin('product_prices', function ($join) {
            $join->on('products.id', '=', 'product_prices.product_id')
                 ->where('product_prices.is_active', true);
        })
        ->selectRaw('products.*, MIN(product_prices.price) as min_price')
        ->groupBy('products.id')
        ->orderBy('min_price', $direction);
    }

    // Accessors
    public function getMinPriceAttribute()
    {
        return $this->activePrices()->min('price');
    }

    public function getMaxPriceAttribute()
    {
        return $this->activePrices()->max('price');
    }

    public function getAvgPriceAttribute()
    {
        return $this->activePrices()->avg('price');
    }

    public function getPriceCountAttribute()
    {
        return $this->activePrices()->count();
    }

    public function getMainImageAttribute()
    {
        $images = $this->images ?? [];
        if (!empty($images)) {
            $image = $images[0];
            // Check if image is an external URL
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }
            return $image;
        }
        
        // Fallback to image_url if available
        if ($this->image_url) {
            return $this->image_url;
        }
        
        return null;
    }

    public function getImageUrlsAttribute()
    {
        $images = $this->images ?? [];
        $urls = array_map(function ($image) {
            // Check if image is an external URL
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }
            return Storage::disk('public')->url($image);
        }, $images);
        
        // Add image_url if available and not already in the array
        if ($this->image_url && !in_array($this->image_url, $urls)) {
            $urls[] = $this->image_url;
        }
        
        return $urls;
    }

    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            // Check if thumbnail is an external URL
            if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
                return $this->thumbnail;
            }
            return Storage::disk('public')->url($this->thumbnail);
        }
        
        $mainImage = $this->main_image;
        if ($mainImage) {
            // Check if mainImage is an external URL
            if (filter_var($mainImage, FILTER_VALIDATE_URL)) {
                return $mainImage;
            }
            return Storage::disk('public')->url($mainImage);
        }
        
        // Fallback to image_url if available
        if ($this->image_url) {
            return $this->image_url;
        }
        
        return null;
    }

    public function getIsInStockAttribute()
    {
        return $this->activePrices()->where('stock', '>', 0)->exists();
    }

    public function getStockQuantityAttribute()
    {
        return $this->activePrices()->sum('stock');
    }

    public function getFormattedWeightAttribute()
    {
        if (!$this->weight) return null;
        
        if ($this->weight < 1) {
            return ($this->weight * 1000) . ' g';
        }
        
        return $this->weight . ' kg';
    }

    // Image Handling Methods
    public function addImage($imagePath)
    {
        $images = $this->images ?? [];
        $images[] = $imagePath;
        $this->images = $images;
        
        // Set as thumbnail if first image
        if (count($images) === 1 && !$this->thumbnail) {
            $this->thumbnail = $imagePath;
        }
        
        return $this;
    }

    public function removeImage($imagePath)
    {
        $images = $this->images ?? [];
        $images = array_filter($images, function ($image) use ($imagePath) {
            return $image !== $imagePath;
        });
        
        $this->images = array_values($images);
        
        // Update thumbnail if removed
        if ($this->thumbnail === $imagePath) {
            $this->thumbnail = !empty($images) ? $images[0] : null;
        }
        
        // Delete file from storage
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        
        return $this;
    }

    public function clearImages()
    {
        $images = $this->images ?? [];
        
        // Delete all image files
        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image)) {
                Storage::disk('public')->delete($image);
            }
        }
        
        // Delete thumbnail
        if ($this->thumbnail && Storage::disk('public')->exists($this->thumbnail)) {
            Storage::disk('public')->delete($this->thumbnail);
        }
        
        $this->images = [];
        $this->thumbnail = null;
        
        return $this;
    }

    public function reorderImages($orderedImages)
    {
        $currentImages = $this->images ?? [];
        $validImages = array_intersect($orderedImages, $currentImages);
        
        $this->images = array_values($validImages);
        
        // Update thumbnail to first image
        if (!empty($validImages) && !$this->thumbnail) {
            $this->thumbnail = $validImages[0];
        }
        
        return $this;
    }

    // Barcode Validation Methods
    public static function validateBarcode($barcode)
    {
        if (empty($barcode)) {
            return ['valid' => false, 'message' => 'Barcode cannot be empty'];
        }

        // Remove any non-numeric characters
        $cleanBarcode = preg_replace('/[^0-9]/', '', $barcode);
        
        if (strlen($cleanBarcode) !== strlen($barcode)) {
            return ['valid' => false, 'message' => 'Barcode must contain only numbers'];
        }

        // Check length (EAN-8, EAN-13, UPC-A, UPC-E)
        $validLengths = [8, 12, 13, 14];
        if (!in_array(strlen($cleanBarcode), $validLengths)) {
            return [
                'valid' => false, 
                'message' => 'Barcode must be 8, 12, 13, or 14 digits long'
            ];
        }

        // Validate checksum for EAN-13 and UPC-A
        if (strlen($cleanBarcode) === 13 || strlen($cleanBarcode) === 12) {
            if (!self::validateEANChecksum($cleanBarcode)) {
                return ['valid' => false, 'message' => 'Invalid barcode checksum'];
            }
        }

        return ['valid' => true, 'message' => 'Valid barcode'];
    }

    private static function validateEANChecksum($barcode)
    {
        $length = strlen($barcode);
        $checkDigit = (int) substr($barcode, -1);
        $code = substr($barcode, 0, $length - 1);
        
        $sum = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $digit = (int) $code[$i];
            if ($i % 2 === 0) {
                $sum += $digit;
            } else {
                $sum += $digit * 3;
            }
        }
        
        $calculatedCheckDigit = (10 - ($sum % 10)) % 10;
        
        return $calculatedCheckDigit === $checkDigit;
    }

    public function isValidBarcode()
    {
        if (!$this->barcode) return false;
        
        $validation = self::validateBarcode($this->barcode);
        return $validation['valid'];
    }

    public function isDuplicateBarcode($excludeId = null)
    {
        if (!$this->barcode) return false;
        
        $query = static::where('barcode', $this->barcode);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        } elseif ($this->id) {
            $query->where('id', '!=', $this->id);
        }
        
        return $query->exists();
    }

    // Utility Methods
    public function approve($notes = null)
    {
        $this->admin_approved = true;
        $this->approval_notes = $notes;
        $this->status = 'published';
        
        return $this->save();
    }

    public function reject($notes = null)
    {
        $this->admin_approved = false;
        $this->approval_notes = $notes;
        $this->status = 'rejected';
        
        return $this->save();
    }

    public function toggleFeatured()
    {
        $this->is_featured = !$this->is_featured;
        
        return $this->save();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name . ' (Copy)';
        $duplicate->slug = null; // Will be auto-generated
        $duplicate->sku = null; // Will be auto-generated
        $duplicate->barcode = null;
        $duplicate->admin_approved = false;
        $duplicate->status = 'draft';
        $duplicate->is_featured = false;
        
        $duplicate->save();
        
        return $duplicate;
    }
}
