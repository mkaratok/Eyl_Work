<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ProductService
{
    protected $allowedImageTypes = ['jpg', 'jpeg', 'png', 'webp'];
    protected $maxImageSize = 5 * 1024 * 1024; // 5MB
    protected $thumbnailSizes = [
        'small' => [150, 150],
        'medium' => [300, 300],
        'large' => [600, 600]
    ];

    /**
     * Create a new product
     */
    public function createProduct(array $data, $createdBy = null): Product
    {
        DB::beginTransaction();
        
        try {
            // Validate barcode if provided
            if (!empty($data['barcode'])) {
                $this->validateBarcodeUniqueness($data['barcode']);
                $this->validateBarcodeFormat($data['barcode']);
            }

            // Validate category exists
            if (!empty($data['category_id'])) {
                $this->validateCategory($data['category_id']);
            }

            // Process images
            if (!empty($data['images'])) {
                $data['images'] = $this->processImages($data['images']);
            }

            // Set creator
            if ($createdBy) {
                $data['created_by'] = $createdBy;
            }

            // Set default status
            if (empty($data['status'])) {
                $data['status'] = 'draft';
            }

            // Handle stock_quantity properly
            if (isset($data['stock_quantity'])) {
                $data['stock_quantity'] = (int) $data['stock_quantity'];
            }
            
            // Ensure proper data types
            if (isset($data['category_id'])) {
                $data['category_id'] = (int) $data['category_id'];
            }
            
            if (isset($data['price'])) {
                $data['price'] = (float) $data['price'];
            }
            
            if (isset($data['is_active'])) {
                $data['is_active'] = (bool) $data['is_active'];
            }

            $product = Product::create($data);

            DB::commit();

            Log::info('Product created successfully', [
                'product_id' => $product->id,
                'name' => $product->name,
                'created_by' => $createdBy
            ]);

            return $product;

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded images on failure
            if (!empty($data['images'])) {
                $this->cleanupImages($data['images']);
            }

            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        DB::beginTransaction();
        
        try {
            $originalImages = $product->images ?? [];

            // Validate barcode if changed
            if (!empty($data['barcode']) && $data['barcode'] !== $product->barcode) {
                $this->validateBarcodeUniqueness($data['barcode'], $product->id);
                $this->validateBarcodeFormat($data['barcode']);
            }

            // Validate category if changed
            if (!empty($data['category_id']) && $data['category_id'] !== $product->category_id) {
                $this->validateCategory($data['category_id']);
            }

            // Process new images
            if (isset($data['images'])) {
                if (is_array($data['images']) && !empty($data['images'])) {
                    $data['images'] = $this->processImages($data['images'], $originalImages);
                } else {
                    // Clear images if empty array provided
                    $this->cleanupImages($originalImages);
                    $data['images'] = [];
                    $data['thumbnail'] = null;
                }
            }

            $product->update($data);

            DB::commit();

            Log::info('Product updated successfully', [
                'product_id' => $product->id,
                'name' => $product->name
            ]);

            return $product->fresh();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Product update failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            throw $e;
        }
    }

    /**
     * Delete a product
     */
    public function deleteProduct(Product $product): bool
    {
        DB::beginTransaction();
        
        try {
            // Clean up images
            $this->cleanupImages($product->images ?? []);
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            // Soft delete the product
            $product->delete();

            DB::commit();

            Log::info('Product deleted successfully', [
                'product_id' => $product->id,
                'name' => $product->name
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Product deletion failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Process and upload images
     */
    public function processImages(array $images, array $existingImages = []): array
    {
        $processedImages = [];
        $keepExistingImages = [];

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                // New uploaded file
                $processedImages[] = $this->uploadAndOptimizeImage($image);
            } elseif (is_string($image) && in_array($image, $existingImages)) {
                // Keep existing image
                $keepExistingImages[] = $image;
            }
        }

        // Clean up removed images
        $imagesToRemove = array_diff($existingImages, $keepExistingImages);
        $this->cleanupImages($imagesToRemove);

        return array_merge($keepExistingImages, $processedImages);
    }

    /**
     * Upload and optimize a single image
     */
    public function uploadAndOptimizeImage(UploadedFile $file): string
    {
        // Validate file
        $this->validateImageFile($file);

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = 'products/' . date('Y/m');
        $fullPath = $path . '/' . $filename;

        // Create optimized image
        $image = Image::make($file);
        
        // Resize if too large (max 1200px width)
        if ($image->width() > 1200) {
            $image->resize(1200, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Optimize quality
        $image->encode($file->getClientOriginalExtension(), 85);

        // Store the optimized image
        Storage::disk('public')->put($fullPath, $image->stream());

        // Generate thumbnails
        $this->generateThumbnails($image, $path, pathinfo($filename, PATHINFO_FILENAME));

        return $fullPath;
    }

    /**
     * Generate thumbnails for an image
     */
    protected function generateThumbnails($image, $path, $filename): void
    {
        foreach ($this->thumbnailSizes as $size => [$width, $height]) {
            $thumbnail = clone $image;
            $thumbnail->fit($width, $height);
            
            $thumbnailPath = $path . '/thumbs/' . $filename . '_' . $size . '.jpg';
            Storage::disk('public')->put($thumbnailPath, $thumbnail->encode('jpg', 80)->stream());
        }
    }

    /**
     * Validate image file
     */
    protected function validateImageFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxImageSize) {
            throw new \InvalidArgumentException('Image size cannot exceed 5MB');
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedImageTypes)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed types: ' . implode(', ', $this->allowedImageTypes));
        }

        // Check if it's a valid image
        if (!getimagesize($file->getPathname())) {
            throw new \InvalidArgumentException('Invalid image file');
        }
    }

    /**
     * Clean up image files
     */
    protected function cleanupImages(array $images): void
    {
        foreach ($images as $imagePath) {
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
                
                // Also delete thumbnails
                $pathInfo = pathinfo($imagePath);
                $thumbnailPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'];
                
                foreach (array_keys($this->thumbnailSizes) as $size) {
                    $thumbFile = $thumbnailPath . '_' . $size . '.jpg';
                    if (Storage::disk('public')->exists($thumbFile)) {
                        Storage::disk('public')->delete($thumbFile);
                    }
                }
            }
        }
    }

    /**
     * Validate barcode format
     */
    public function validateBarcodeFormat(string $barcode): void
    {
        $validation = Product::validateBarcode($barcode);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }
    }

    /**
     * Validate barcode uniqueness
     */
    public function validateBarcodeUniqueness(string $barcode, int $excludeId = null): void
    {
        $query = Product::where('barcode', $barcode);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        if ($query->exists()) {
            throw new \InvalidArgumentException('Barcode already exists');
        }
    }

    /**
     * Validate category exists and is active
     */
    protected function validateCategory(int $categoryId): void
    {
        $category = Category::find($categoryId);
        
        if (!$category) {
            throw new \InvalidArgumentException('Category not found');
        }
        
        if (!$category->is_active) {
            throw new \InvalidArgumentException('Category is not active');
        }
    }

    /**
     * Check for duplicate products
     */
    public function checkDuplicates(array $criteria): array
    {
        $query = Product::query();
        
        // Check by barcode
        if (!empty($criteria['barcode'])) {
            $query->orWhere('barcode', $criteria['barcode']);
        }
        
        // Check by name and brand combination
        if (!empty($criteria['name']) && !empty($criteria['brand'])) {
            $query->orWhere(function ($q) use ($criteria) {
                $q->where('name', 'like', '%' . $criteria['name'] . '%')
                  ->where('brand', $criteria['brand']);
            });
        }
        
        // Check by SKU
        if (!empty($criteria['sku'])) {
            $query->orWhere('sku', $criteria['sku']);
        }
        
        return $query->get()->toArray();
    }

    /**
     * Bulk approve products
     */
    public function bulkApprove(array $productIds, string $notes = null): int
    {
        $updated = Product::whereIn('id', $productIds)
            ->where('admin_approved', false)
            ->update([
                'admin_approved' => true,
                'status' => 'published',
                'approval_notes' => $notes,
                'updated_at' => now()
            ]);

        Log::info('Bulk product approval completed', [
            'product_ids' => $productIds,
            'updated_count' => $updated,
            'notes' => $notes
        ]);

        return $updated;
    }

    /**
     * Bulk reject products
     */
    public function bulkReject(array $productIds, string $notes = null): int
    {
        $updated = Product::whereIn('id', $productIds)
            ->update([
                'admin_approved' => false,
                'status' => 'rejected',
                'approval_notes' => $notes,
                'updated_at' => now()
            ]);

        Log::info('Bulk product rejection completed', [
            'product_ids' => $productIds,
            'updated_count' => $updated,
            'notes' => $notes
        ]);

        return $updated;
    }

    /**
     * Get product statistics
     */
    public function getProductStats(): array
    {
        return [
            'total' => Product::count(),
            'published' => Product::published()->count(),
            'pending' => Product::pending()->count(),
            'approved' => Product::approved()->count(),
            'featured' => Product::featured()->count(),
            'in_stock' => Product::inStock()->count(),
            'with_images' => Product::withImages()->count(),
            'by_category' => Product::select('category_id')
                ->with('category:id,name')
                ->groupBy('category_id')
                ->selectRaw('category_id, count(*) as count')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->category->name ?? 'Unknown' => $item->count];
                }),
            'by_brand' => Product::select('brand')
                ->whereNotNull('brand')
                ->groupBy('brand')
                ->selectRaw('brand, count(*) as count')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'brand'),
        ];
    }

    /**
     * Search products with advanced filters
     */
    public function searchProducts(array $filters, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Product::with(['category', 'activePrices']);
        
        // Add image fields to select
        $query->addSelect([
            'id',
            'name',
            'description',
            'category_id',
            'brand',
            'price',
            'images',
            'thumbnail',
            'image_url',
            'is_active',
            'status',
            'admin_approved',
            'created_at',
            'updated_at'
        ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (!empty($filters['brand'])) {
            $query->byBrand($filters['brand']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['admin_approved'])) {
            if ($filters['admin_approved']) {
                $query->approved();
            } else {
                $query->pending();
            }
        }

        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->byPriceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null);
        }

        if (!empty($filters['in_stock']) && $filters['in_stock']) {
            $query->inStock();
        }

        if (!empty($filters['featured']) && $filters['featured']) {
            $query->featured();
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'price':
                $query->orderByPrice($sortDirection);
                break;
            case 'popularity':
                $query->orderByPopularity();
                break;
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
                break;
        }

        return $query->paginate($perPage);
    }
}
