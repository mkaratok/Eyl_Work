<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'merchant_id',
        'parent_id',
        'level',
        'path',
        'icon',
        'image',
        'meta_title',
        'meta_description',
        'sort_order',
        'is_active',
        'is_featured',
        'google_category_id',
        'google_category_path',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'level' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected $dates = [
        'deleted_at',
    ];

    // Boot method for auto-generating slug and path
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            
            // Auto-calculate level and path
            if ($category->parent_id) {
                $parent = static::find($category->parent_id);
                $category->level = $parent->level + 1;
                $category->path = $parent->path . '/' . $category->slug;
            } else {
                $category->level = 0;
                $category->path = $category->slug;
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeWithPath($query, $path)
    {
        return $query->where('path', 'like', $path . '%');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('google_category_path', 'like', "%{$term}%");
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $names = [];
        $category = $this;
        
        while ($category) {
            array_unshift($names, $category->name);
            $category = $category->parent;
        }
        
        return implode(' > ', $names);
    }

    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        $category = $this;
        
        while ($category) {
            array_unshift($breadcrumb, [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'path' => $category->path,
            ]);
            $category = $category->parent;
        }
        
        return $breadcrumb;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/categories/' . $this->image) : null;
    }

    // Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // Helper Methods
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function getDescendants()
    {
        return static::where('path', 'like', $this->path . '/%')->get();
    }

    public function getAncestors()
    {
        $ancestors = collect();
        $category = $this->parent;
        
        while ($category) {
            $ancestors->prepend($category);
            $category = $category->parent;
        }
        
        return $ancestors;
    }

    public function isDescendantOf(Category $category): bool
    {
        return Str::startsWith($this->path, $category->path . '/');
    }

    public function isAncestorOf(Category $category): bool
    {
        return Str::startsWith($category->path, $this->path . '/');
    }

    // Tree building methods
    public static function getTree($parentId = null, $activeOnly = true)
    {
        $query = static::with(['children' => function ($q) use ($activeOnly) {
            if ($activeOnly) {
                $q->active();
            }
            $q->orderBy('sort_order');
        }]);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('sort_order')->get();
    }

    public static function getFlatTree($activeOnly = true)
    {
        $query = static::with('parent');
        
        if ($activeOnly) {
            $query->active();
        }
        
        return $query->orderBy('path')->get();
    }
}
