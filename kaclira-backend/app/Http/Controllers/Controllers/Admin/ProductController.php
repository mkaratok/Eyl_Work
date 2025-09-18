<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\ProductImportExportService;
use App\Services\XmlProductImportService;
use App\Http\Requests\BulkProductImportRequest;
use App\Http\Requests\XmlProductImportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected $productService;
    protected $importExportService;
    protected $xmlImportService;

    public function __construct(
        ProductService $productService, 
        ProductImportExportService $importExportService,
        XmlProductImportService $xmlImportService
    ) {
        $this->productService = $productService;
        $this->importExportService = $importExportService;
        $this->xmlImportService = $xmlImportService;
        $this->middleware(['auth:api', 'role:admin']);
    }

    /**
     * Get all products for admin (including pending, rejected)
     * GET /api/admin/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'category_id',
                'brand',
                'status',
                'admin_approved',
                'min_price',
                'max_price',
                'in_stock',
                'featured',
                'sort_by',
                'sort_direction'
            ]);

            $perPage = min($request->get('per_page', 15), 100);
            $products = $this->productService->searchProducts($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single product for admin
     * GET /api/admin/products/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $product = Product::with([
                'category',
                'creator',
                'activePrices.seller',
                'priceHistory' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(20);
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new product (Admin)
     * POST /api/admin/products
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the incoming request with simplified rules for admin
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|integer|exists:categories,id',
            'brand' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'image_url' => 'nullable|url|max:500',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $adminId = Auth::id();
            
            // Set admin defaults
            $validated['status'] = 'published';
            $validated['admin_approved'] = true;
            $validated['description'] = $validated['description'] ?: 'Product description';
            $validated['brand'] = $validated['brand'] ?: 'Default Brand';
            
            $product = $this->productService->createProduct($validated, $adminId);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load(['category'])
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending products for approval
     * GET /api/admin/products/pending
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 50);
            
            $products = Product::with(['category', 'creator'])
                ->pending()
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a product
     * POST /api/admin/products/{id}/approve
     */
    public function approve($id, Request $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            
            $notes = $request->get('notes');
            $success = $product->approve($notes);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product approved successfully',
                    'data' => $product->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve product'
            ], 500);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a product
     * POST /api/admin/products/{id}/reject
     */
    public function reject($id, Request $request): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|max:1000'
        ]);

        try {
            $product = Product::findOrFail($id);
            
            $notes = $request->get('notes');
            $success = $product->reject($notes);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product rejected successfully',
                    'data' => $product->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject product'
            ], 500);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk approve products
     * POST /api/admin/products/bulk-approve
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $productIds = $request->get('product_ids');
            $notes = $request->get('notes');
            
            $updated = $this->productService->bulkApprove($productIds, $notes);

            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$updated} products",
                'data' => ['updated_count' => $updated]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk approval failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk reject products
     * POST /api/admin/products/bulk-reject
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'notes' => 'required|string|max:1000'
        ]);

        try {
            $productIds = $request->get('product_ids');
            $notes = $request->get('notes');
            
            $updated = $this->productService->bulkReject($productIds, $notes);

            return response()->json([
                'success' => true,
                'message' => "Successfully rejected {$updated} products",
                'data' => ['updated_count' => $updated]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk rejection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle featured status
     * POST /api/admin/products/{id}/toggle-featured
     */
    public function toggleFeatured($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $success = $product->toggleFeatured();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => $product->is_featured ? 'Product featured' : 'Product unfeatured',
                    'data' => $product->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured status'
            ], 500);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product
     * DELETE /api/admin/products/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $success = $this->productService->deleteProduct($product);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product'
            ], 500);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete products
     * POST /api/admin/products/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id'
        ]);

        try {
            $productIds = $request->get('product_ids');
            $deleted = 0;

            foreach ($productIds as $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $this->productService->deleteProduct($product);
                    $deleted++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deleted} products",
                'data' => ['deleted_count' => $deleted]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product statistics for admin dashboard
     * GET /api/admin/products/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->productService->getProductStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for duplicate products
     * POST /api/admin/products/check-duplicates
     */
    public function checkDuplicates(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => 'nullable|string',
            'name' => 'nullable|string',
            'brand' => 'nullable|string',
            'sku' => 'nullable|string'
        ]);

        try {
            $criteria = $request->only(['barcode', 'name', 'brand', 'sku']);
            $duplicates = $this->productService->checkDuplicates($criteria);

            return response()->json([
                'success' => true,
                'data' => $duplicates,
                'meta' => [
                    'found' => count($duplicates),
                    'criteria' => $criteria
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate barcode
     * POST /api/admin/products/validate-barcode
     */
    public function validateBarcode(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => 'required|string',
            'exclude_id' => 'nullable|integer|exists:products,id'
        ]);

        try {
            $barcode = $request->get('barcode');
            $excludeId = $request->get('exclude_id');

            // Format validation
            $formatValidation = Product::validateBarcode($barcode);
            
            if (!$formatValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $formatValidation['message'],
                    'data' => ['valid' => false, 'type' => 'format']
                ]);
            }

            // Uniqueness validation
            try {
                $this->productService->validateBarcodeUniqueness($barcode, $excludeId);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Barcode is valid and unique',
                    'data' => ['valid' => true]
                ]);

            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => ['valid' => false, 'type' => 'duplicate']
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import products from XML file
     * POST /api/admin/products/import-xml
     */
    public function importXml(XmlProductImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $config = $request->getImportConfig();

            $results = $this->xmlImportService->importFromXml($file, $config);

            return response()->json([
                'success' => true,
                'message' => 'XML products imported successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'XML import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download XML import template
     * GET /api/admin/products/xml-template
     */
    public function downloadXmlTemplate(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'custom'); // custom or google_merchant
            
            if ($type === 'google_merchant') {
                $template = $this->xmlImportService->generateGoogleMerchantTemplate();
                $filename = 'google_merchant_template.xml';
            } else {
                $template = $this->xmlImportService->generateXmlTemplate();
                $filename = 'custom_xml_template.xml';
            }

            $filePath = 'templates/' . $filename;
            Storage::put($filePath, $template);
            $downloadUrl = Storage::url($filePath);

            return response()->json([
                'success' => true,
                'message' => 'XML template generated successfully',
                'data' => [
                    'file_path' => $filePath,
                    'download_url' => $downloadUrl,
                    'template_type' => $type
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate XML template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import products from Excel/CSV
     * POST /api/admin/products/import
     */
    public function import(BulkProductImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $config = $request->getImportConfig();

            $results = $this->importExportService->importProducts($file, $config);

            return response()->json([
                'success' => true,
                'message' => 'Products imported successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import without actually importing
     * POST /api/admin/products/import/preview
     */
    public function importPreview(BulkProductImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $config = $request->getImportConfig();

            $results = $this->importExportService->previewImport($file, $config);

            return response()->json([
                'success' => true,
                'message' => 'Import preview completed',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import preview failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export products to Excel/CSV
     * GET /api/admin/products/export
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'category_id',
                'brand',
                'created_from',
                'created_to'
            ]);

            $format = $request->get('format', 'xlsx');
            
            if (!in_array($format, ['xlsx', 'csv'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid format. Use xlsx or csv'
                ], 422);
            }

            $filePath = $this->importExportService->exportProducts($filters, $format);
            $downloadUrl = Storage::url($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Products exported successfully',
                'data' => [
                    'file_path' => $filePath,
                    'download_url' => $downloadUrl,
                    'format' => $format
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download import template
     * GET /api/admin/products/import/template
     */
    public function importTemplate(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'xlsx');
            
            if (!in_array($format, ['xlsx', 'csv'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid format. Use xlsx or csv'
                ], 422);
            }

            $filePath = $this->importExportService->getImportTemplate($format);
            $downloadUrl = Storage::url($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Template generated successfully',
                'data' => [
                    'file_path' => $filePath,
                    'download_url' => $downloadUrl,
                    'format' => $format
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate import file
     * POST /api/admin/products/import/validate
     */
    public function validateImportFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'mapping' => 'required|array'
        ]);

        try {
            $file = $request->file('file');
            $mapping = $request->get('mapping');

            $validation = $this->importExportService->validateImportFile($file, $mapping);

            return response()->json([
                'success' => $validation['valid'],
                'message' => $validation['valid'] ? 'File is valid' : 'File validation failed',
                'data' => $validation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
