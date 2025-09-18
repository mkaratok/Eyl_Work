<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class ProductImportExportService
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Import products from Excel/CSV file
     */
    public function importProducts(UploadedFile $file, array $config): array
    {
        try {
            $results = [
                'total_rows' => 0,
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
                'warnings' => []
            ];

            // Create import instance
            $import = new ProductImport($config, $this->productService);
            
            // Process the file
            Excel::import($import, $file);
            
            // Get results from import
            $results = array_merge($results, $import->getResults());

            return $results;

        } catch (\Exception $e) {
            Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            throw $e;
        }
    }

    /**
     * Preview import without actually importing
     */
    public function previewImport(UploadedFile $file, array $config): array
    {
        try {
            $config['preview_only'] = true;
            return $this->importProducts($file, $config);

        } catch (\Exception $e) {
            Log::error('Product import preview failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            throw $e;
        }
    }

    /**
     * Export products to Excel/CSV
     */
    public function exportProducts(array $filters = [], string $format = 'xlsx'): string
    {
        try {
            $export = new ProductExport($filters);
            $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.' . $format;
            $path = 'exports/' . $filename;

            Excel::store($export, $path, 'local');

            return $path;

        } catch (\Exception $e) {
            Log::error('Product export failed', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            throw $e;
        }
    }

    /**
     * Get import template
     */
    public function getImportTemplate(string $format = 'xlsx'): string
    {
        try {
            $template = new ProductImportTemplate();
            $filename = 'product_import_template.' . $format;
            $path = 'templates/' . $filename;

            Excel::store($template, $path, 'local');

            return $path;

        } catch (\Exception $e) {
            Log::error('Template generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Validate import file structure
     */
    public function validateImportFile(UploadedFile $file, array $mapping): array
    {
        try {
            $validation = [
                'valid' => true,
                'errors' => [],
                'warnings' => [],
                'columns' => [],
                'sample_data' => []
            ];

            // Read first few rows to validate structure
            $data = Excel::toCollection(new class implements ToCollection {
                public function collection(Collection $collection) {
                    return $collection;
                }
            }, $file)->first();

            if ($data->isEmpty()) {
                $validation['valid'] = false;
                $validation['errors'][] = 'File is empty or invalid';
                return $validation;
            }

            // Get headers
            $headers = $data->first()->toArray();
            $validation['columns'] = array_filter($headers);

            // Check required columns
            $requiredMappings = ['name', 'description', 'brand'];
            foreach ($requiredMappings as $required) {
                if (!isset($mapping[$required]) || !in_array($mapping[$required], $headers)) {
                    $validation['errors'][] = "Required column '{$required}' not found in mapping";
                    $validation['valid'] = false;
                }
            }

            // Get sample data (first 5 rows)
            $validation['sample_data'] = $data->take(6)->skip(1)->toArray();

            return $validation;

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['File validation failed: ' . $e->getMessage()],
                'warnings' => [],
                'columns' => [],
                'sample_data' => []
            ];
        }
    }
}

/**
 * Product Import Class
 */
class ProductImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $config;
    protected $productService;
    protected $results;
    protected $categoryCache = [];

    public function __construct(array $config, ProductService $productService)
    {
        $this->config = $config;
        $this->productService = $productService;
        $this->results = [
            'total_rows' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => []
        ];
    }

    public function collection(Collection $rows)
    {
        $this->results['total_rows'] = $rows->count();

        foreach ($rows as $index => $row) {
            $this->results['processed']++;
            
            try {
                $this->processRow($row->toArray(), $index + 2); // +2 for header row and 0-based index
            } catch (\Exception $e) {
                $this->results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    protected function processRow(array $row, int $rowNumber)
    {
        // Map columns to product fields
        $productData = $this->mapRowToProduct($row);

        // Validate required fields
        if (empty($productData['name']) || empty($productData['description']) || empty($productData['brand'])) {
            $this->results['skipped']++;
            $this->results['warnings'][] = "Row {$rowNumber}: Missing required fields (name, description, brand)";
            return;
        }

        // Preview mode - don't actually create/update
        if ($this->config['preview_only']) {
            $this->results['created']++;
            return;
        }

        // Check for duplicates
        $existing = null;
        if (!empty($productData['sku'])) {
            $existing = Product::where('sku', $productData['sku'])->first();
        } elseif (!empty($productData['barcode'])) {
            $existing = Product::where('barcode', $productData['barcode'])->first();
        }

        try {
            if ($existing) {
                // Handle existing product
                if ($this->config['import_type'] === 'create') {
                    if ($this->config['skip_duplicates']) {
                        $this->results['skipped']++;
                        $this->results['warnings'][] = "Row {$rowNumber}: Product already exists (SKU: {$productData['sku']})";
                        return;
                    } else {
                        throw new \Exception("Product already exists (SKU: {$productData['sku']})");
                    }
                } else {
                    // Update existing product
                    $this->productService->updateProduct($existing, $productData);
                    $this->results['updated']++;
                }
            } else {
                // Create new product
                if ($this->config['import_type'] === 'update') {
                    $this->results['skipped']++;
                    $this->results['warnings'][] = "Row {$rowNumber}: Product not found for update";
                    return;
                }

                $this->productService->createProduct($productData, $this->config['defaults']['created_by']);
                $this->results['created']++;
            }

        } catch (\Exception $e) {
            $this->results['errors'][] = "Row {$rowNumber}: " . $e->getMessage();
        }
    }

    protected function mapRowToProduct(array $row): array
    {
        $mapping = $this->config['mapping'];
        $productData = [];

        // Map basic fields
        foreach ($mapping as $field => $column) {
            if (isset($row[$column]) && !empty($row[$column])) {
                $productData[$field] = $row[$column];
            }
        }

        // Handle category mapping
        if (!empty($productData['category'])) {
            $categoryId = $this->findOrCreateCategory($productData['category']);
            $productData['category_id'] = $categoryId;
            unset($productData['category']);
        } else {
            $productData['category_id'] = $this->config['defaults']['category_id'];
        }

        // Apply defaults
        foreach ($this->config['defaults'] as $key => $value) {
            if (!isset($productData[$key]) && $value !== null) {
                $productData[$key] = $value;
            }
        }

        // Parse specifications if present
        if (isset($productData['specifications']) && is_string($productData['specifications'])) {
            $specs = [];
            $pairs = explode(';', $productData['specifications']);
            foreach ($pairs as $pair) {
                if (strpos($pair, ':') !== false) {
                    [$key, $value] = explode(':', $pair, 2);
                    $specs[trim($key)] = trim($value);
                }
            }
            $productData['specifications'] = $specs;
        }

        return $productData;
    }

    protected function findOrCreateCategory(string $categoryName): ?int
    {
        if (isset($this->categoryCache[$categoryName])) {
            return $this->categoryCache[$categoryName];
        }

        $category = Category::where('name', $categoryName)->first();
        
        if (!$category) {
            // Create category if not found
            $category = Category::create([
                'name' => $categoryName,
                'slug' => \Str::slug($categoryName),
                'is_active' => true,
                'parent_id' => null
            ]);
        }

        $this->categoryCache[$categoryName] = $category->id;
        return $category->id;
    }

    public function batchSize(): int
    {
        return $this->config['batch_size'] ?? 100;
    }

    public function chunkSize(): int
    {
        return $this->config['batch_size'] ?? 100;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

/**
 * Product Export Class
 */
class ProductExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Product::with(['category', 'creator']);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['brand'])) {
            $query->where('brand', $this->filters['brand']);
        }

        if (!empty($this->filters['created_from'])) {
            $query->where('created_at', '>=', $this->filters['created_from']);
        }

        if (!empty($this->filters['created_to'])) {
            $query->where('created_at', '<=', $this->filters['created_to']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Description',
            'Short Description',
            'Brand',
            'Model',
            'SKU',
            'Barcode',
            'Category',
            'Weight',
            'Status',
            'Admin Approved',
            'Is Featured',
            'Created By',
            'Created At',
            'Updated At'
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->slug,
            $product->description,
            $product->short_description,
            $product->brand,
            $product->model,
            $product->sku,
            $product->barcode,
            $product->category->name ?? '',
            $product->weight,
            $product->status,
            $product->admin_approved ? 'Yes' : 'No',
            $product->is_featured ? 'Yes' : 'No',
            $product->creator->name ?? '',
            $product->created_at->format('Y-m-d H:i:s'),
            $product->updated_at->format('Y-m-d H:i:s')
        ];
    }
}

/**
 * Product Import Template Class
 */
class ProductImportTemplate implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'Sample Product 1',
                'sample-product-1',
                'This is a sample product description',
                'Short description',
                'Sample Brand',
                'Model X',
                'SKU001',
                '1234567890123',
                'Electronics',
                '1.5',
                'Color:Red;Size:Large',
                'draft'
            ],
            [
                'Sample Product 2',
                'sample-product-2',
                'Another sample product description',
                'Another short description',
                'Another Brand',
                'Model Y',
                'SKU002',
                '9876543210987',
                'Clothing',
                '0.3',
                'Material:Cotton;Size:Medium',
                'pending'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'slug',
            'description',
            'short_description',
            'brand',
            'model',
            'sku',
            'barcode',
            'category',
            'weight',
            'specifications',
            'status'
        ];
    }
}
