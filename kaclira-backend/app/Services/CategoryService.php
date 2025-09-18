<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class CategoryService
{
    const CACHE_KEY_TREE = 'categories_tree';
    const CACHE_KEY_FLAT = 'categories_flat';
    const CACHE_KEY_GOOGLE = 'google_categories';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get hierarchical category tree with caching
     */
    public function getCategoryTree(bool $activeOnly = true, bool $useCache = true): Collection
    {
        $cacheKey = self::CACHE_KEY_TREE . ($activeOnly ? '_active' : '_all');
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $categories = Category::getTree(null, $activeOnly);
        
        if ($useCache) {
            Cache::put($cacheKey, $categories, self::CACHE_TTL);
        }

        return $categories;
    }

    /**
     * Get flat category list with caching
     */
    public function getFlatCategories(bool $activeOnly = true, bool $useCache = true): Collection
    {
        $cacheKey = self::CACHE_KEY_FLAT . ($activeOnly ? '_active' : '_all');
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $categories = Category::getFlatTree($activeOnly);
        
        if ($useCache) {
            Cache::put($cacheKey, $categories, self::CACHE_TTL);
        }

        return $categories;
    }

    /**
     * Search categories with caching
     */
    public function searchCategories(string $term, bool $activeOnly = true): Collection
    {
        $cacheKey = "categories_search_" . md5($term) . ($activeOnly ? '_active' : '_all');
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $query = Category::search($term);
        
        if ($activeOnly) {
            $query->active();
        }

        $categories = $query->with('parent')->orderBy('level')->get();
        
        Cache::put($cacheKey, $categories, 1800); // 30 minutes for search results
        
        return $categories;
    }

    /**
     * Create category with automatic path and level calculation
     */
    public function createCategory(array $data): Category
    {
        // Ensure required fields are present
        if (empty($data['name'])) {
            throw new Exception('Category name is required');
        }
        
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        
        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        
        $category = Category::create($data);
        
        $this->clearCache();
        
        return $category->fresh();
    }

    /**
     * Create hierarchical categories from a path string (e.g. "Parent > Child > Grandchild")
     * Returns the ID of the deepest category in the hierarchy
     */
    public function createCategoryFromPath(string $path, string $separator = ' > '): ?int
    {
        if (empty($path)) {
            return null;
        }

        $categoryNames = array_map('trim', explode($separator, $path));
        $parentId = null;
        $lastCategoryId = null;

        foreach ($categoryNames as $index => $categoryName) {
            if (empty($categoryName)) {
                continue;
            }

            // Check if category already exists at this level
            $existingCategory = Category::where('name', $categoryName)
                ->where('parent_id', $parentId)
                ->first();

            if ($existingCategory) {
                $lastCategoryId = $existingCategory->id;
                $parentId = $existingCategory->id;
            } else {
                // Create new category
                $categoryData = [
                    'name' => $categoryName,
                    'parent_id' => $parentId,
                    'is_active' => true,
                    'sort_order' => 0
                ];

                $newCategory = $this->createCategory($categoryData);
                $lastCategoryId = $newCategory->id;
                $parentId = $newCategory->id;
            }
        }

        return $lastCategoryId;
    }

    /**
     * Update category and recalculate paths for children if needed
     */
    public function updateCategory(Category $category, array $data): Category
    {
        $oldPath = $category->path;
        $oldParentId = $category->parent_id;
        
        $category->update($data);
        
        // If parent changed, update all descendant paths
        if (isset($data['parent_id']) && $data['parent_id'] != $oldParentId) {
            $this->updateDescendantPaths($category, $oldPath);
        }
        
        $this->clearCache();
        
        return $category->fresh();
    }

    /**
     * Delete category and handle children
     */
    public function deleteCategory(Category $category, string $action = 'move_to_parent'): bool
    {
        switch ($action) {
            case 'move_to_parent':
                // Move children to parent category
                $category->children()->update(['parent_id' => $category->parent_id]);
                break;
                
            case 'delete_children':
                // Delete all children recursively
                $this->deleteWithChildren($category);
                break;
                
            default:
                // Just delete if no children
                if ($category->hasChildren()) {
                    throw new Exception('Category has children. Please specify action.');
                }
        }
        
        $result = $category->delete();
        $this->clearCache();
        
        return $result;
    }

    /**
     * Sync categories from Google Merchant Center
     */
    public function syncGoogleCategories(): array
    {
        try {
            $googleCategories = $this->fetchGoogleCategories();
            
            $stats = [
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => 0
            ];

            foreach ($googleCategories as $googleCategory) {
                $stats['total']++;
                
                try {
                    $category = $this->syncSingleGoogleCategory($googleCategory);
                    
                    if ($category->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch (Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing Google category', [
                        'category' => $googleCategory,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->clearCache();
            
            Log::info('Google categories sync completed', $stats);
            
            return $stats;
            
        } catch (Exception $e) {
            Log::error('Google categories sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get category suggestions based on product name/description
     */
    public function suggestCategories(string $productText, int $limit = 5): Collection
    {
        $cacheKey = "category_suggestions_" . md5($productText);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Simple keyword matching - can be enhanced with ML/AI
        $keywords = $this->extractKeywords($productText);
        
        $categories = Category::active()
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%")
                          ->orWhere('google_category_path', 'like', "%{$keyword}%");
                }
            })
            ->orderByRaw('CASE 
                WHEN name LIKE ? THEN 1 
                WHEN description LIKE ? THEN 2 
                ELSE 3 END', ["%{$keywords[0]}%", "%{$keywords[0]}%"])
            ->limit($limit)
            ->get();

        Cache::put($cacheKey, $categories, 1800); // 30 minutes
        
        return $categories;
    }

    /**
     * Clear all category caches
     */
    public function clearCache(): void
    {
        $patterns = [
            self::CACHE_KEY_TREE . '*',
            self::CACHE_KEY_FLAT . '*',
            'categories_search_*',
            'category_suggestions_*'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Private helper methods
     */
    private function updateDescendantPaths(Category $category, string $oldPath): void
    {
        $descendants = Category::where('path', 'like', $oldPath . '/%')->get();
        
        foreach ($descendants as $descendant) {
            $newPath = str_replace($oldPath, $category->path, $descendant->path);
            $descendant->update(['path' => $newPath]);
        }
    }

    private function deleteWithChildren(Category $category): void
    {
        $children = $category->children;
        
        foreach ($children as $child) {
            $this->deleteWithChildren($child);
        }
        
        $category->delete();
    }

    private function fetchGoogleCategories(): array
    {
        // Check cache first
        if (Cache::has(self::CACHE_KEY_GOOGLE)) {
            return Cache::get(self::CACHE_KEY_GOOGLE);
        }

        // Fetch from Google Merchant Center API or taxonomy file
        $response = Http::timeout(30)->get('https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt');
        
        if (!$response->successful()) {
            throw new Exception('Failed to fetch Google categories');
        }

        $categories = $this->parseGoogleTaxonomy($response->body());
        
        // Cache for 24 hours
        Cache::put(self::CACHE_KEY_GOOGLE, $categories, 86400);
        
        return $categories;
    }

    private function parseGoogleTaxonomy(string $content): array
    {
        $lines = explode("\n", trim($content));
        $categories = [];
        
        foreach ($lines as $line) {
            if (empty($line) || strpos($line, '#') === 0) {
                continue; // Skip comments and empty lines
            }
            
            $parts = explode(' - ', $line, 2);
            if (count($parts) === 2) {
                $id = trim($parts[0]);
                $path = trim($parts[1]);
                
                $categories[] = [
                    'google_id' => $id,
                    'path' => $path,
                    'levels' => explode(' > ', $path)
                ];
            }
        }
        
        return $categories;
    }

    private function syncSingleGoogleCategory(array $googleCategory): Category
    {
        $levels = $googleCategory['levels'];
        $parentId = null;
        $category = null;
        
        // Create hierarchy level by level
        foreach ($levels as $index => $levelName) {
            $category = Category::firstOrCreate([
                'name' => $levelName,
                'parent_id' => $parentId,
            ], [
                'slug' => \Illuminate\Support\Str::slug($levelName),
                'level' => $index,
                'is_active' => true,
                'google_category_id' => $index === count($levels) - 1 ? $googleCategory['google_id'] : null,
                'google_category_path' => $googleCategory['path'],
            ]);
            
            $parentId = $category->id;
        }
        
        return $category;
    }

    private function extractKeywords(string $text): array
    {
        // Simple keyword extraction - can be enhanced
        $text = strtolower($text);
        $words = preg_split('/\s+/', $text);
        
        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $keywords = array_diff($words, $stopWords);
        
        // Remove words shorter than 3 characters
        $keywords = array_filter($keywords, fn($word) => strlen($word) >= 3);
        
        return array_slice(array_values($keywords), 0, 5); // Top 5 keywords
    }
}