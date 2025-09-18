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
        // Remove the incorrect middleware call
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

            $perPage = min($request->get('per_page', 50), 100);
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
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin product creation failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }
        
        // Log incoming request for debugging
        \Log::info('Admin product creation request received', [
            'user_id' => $user->id,
            'user' => $user->toArray(),
            'request_data' => $request->all(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'headers' => $request->headers->all()
        ]);

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
            $adminId = $user->id;
            
            // Set admin defaults
            $validated['status'] = 'published';
            $validated['admin_approved'] = true;
            $validated['description'] = $validated['description'] ?? 'Product description';
            $validated['brand'] = $validated['brand'] ?? 'Default Brand';
            
            // Ensure proper data types
            if (isset($validated['category_id'])) {
                $validated['category_id'] = (int) $validated['category_id'];
            }
            
            if (isset($validated['price'])) {
                $validated['price'] = (float) $validated['price'];
            }
            
            if (isset($validated['stock_quantity'])) {
                $validated['stock_quantity'] = (int) $validated['stock_quantity'];
            }
            
            if (isset($validated['is_active'])) {
                $validated['is_active'] = (bool) $validated['is_active'];
            }
            
            // Log the product creation attempt
            \Log::info('Admin product creation attempt', [
                'admin_id' => $adminId,
                'product_data' => $validated
            ]);
            
            $product = $this->productService->createProduct($validated, $adminId);

            \Log::info('Product created successfully', [
                'product_id' => $product->id,
                'name' => $product->name,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load(['category'])
            ], 201);

        } catch (\InvalidArgumentException $e) {
            \Log::warning('Product creation failed - invalid argument', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id()
            ]);
            
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
            $productIds = $request->get('productIds');
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
            $productIds = $request->get('productIds');
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
            $productIds = $request->get('productIds');
            $deleted = $this->productService->bulkDelete($productIds);

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
     * Get product statistics
     * GET /api/admin/products/stats
     */
    public function stats(Request $request): JsonResponse
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
                'message' => 'Failed to fetch product statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a product
     * PUT /api/admin/products/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            
            // Validate the incoming request
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'sku' => 'nullable|string|max:50|unique:products,sku,'.$product->id,
                'description' => 'nullable|string|max:5000',
                'category_id' => 'sometimes|required|integer|exists:categories,id',
                'brand' => 'nullable|string|max:100',
                'price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'nullable|integer|min:0',
                'image_url' => 'nullable|url|max:500',
                'is_active' => 'nullable|boolean'
            ]);
            
            // Ensure proper data types
            if (isset($validated['category_id'])) {
                $validated['category_id'] = (int) $validated['category_id'];
            }
            
            if (isset($validated['price'])) {
                $validated['price'] = (float) $validated['price'];
            }
            
            if (isset($validated['stock_quantity'])) {
                $validated['stock_quantity'] = (int) $validated['stock_quantity'];
            }
            
            if (isset($validated['is_active'])) {
                $validated['is_active'] = (bool) $validated['is_active'];
            }
            
            $product->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load(['category'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
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
            $product->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
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
     * Toggle product featured status
     * POST /api/admin/products/{id}/toggle-featured
     */
    public function toggleFeatured($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->is_featured = !$product->is_featured;
            $product->save();
            
            $message = $product->is_featured ? 'Product marked as featured' : 'Product unmarked as featured';
            
            return response()->json([
                'success' => true,
                'message' => $message,
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
                'message' => 'Failed to toggle featured status',
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
            
            $isValid = $this->productService->validateBarcode($barcode, $excludeId);

            return response()->json([
                'success' => true,
                'data' => ['valid' => $isValid]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import products from file
     * POST /api/admin/products/import
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,txt|max:10240' // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $results = $this->importExportService->importFromFile($file);
            
            return response()->json([
                'success' => true,
                'message' => 'Products imported successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import products from XML file
     * POST|HEAD /api/admin/products/import-xml
     */
    public function importXml(Request $request): JsonResponse
    {
        // Handle HEAD requests immediately
        if ($request->isMethod('HEAD')) {
            return response()->json([
                'success' => true,
                'message' => 'XML import endpoint is available'
            ]);
        }
        
        // For POST requests, validate the request
        if ($request->isMethod('POST')) {
            // Validate the request
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'file' => 'required|file|max:51200', // 50MB max
                'import_type' => 'required|in:google_merchant,custom_xml',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
        }
        
        try {
            \Log::info('XML Import Request Received', [
                'url' => $request->url(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('file'),
                'all_inputs' => $request->all(),
                'request_path' => $request->path(),
                'request_segment_count' => $request->segments(),
            ]);
            
            // Only proceed with file processing for POST requests
            if (!$request->isMethod('POST')) {
                return response()->json([
                    'success' => true,
                    'message' => 'XML import endpoint is available'
                ]);
            }
            
            $file = $request->file('file');
            if (!$file) {
                \Log::warning('XML Import: No file uploaded');
                return response()->json([
                    'success' => false,
                    'message' => 'No file uploaded'
                ], 400);
            }
            
            // Get import configuration
            $config = [
                'import_type' => $request->input('import_type', 'custom_xml'),
                'update_existing' => $request->boolean('update_existing', true),
                'auto_approve' => $request->boolean('auto_approve', false),
                'create_categories' => $request->boolean('create_categories', false),
                'default_category_id' => $request->input('default_category_id'),
                'default_brand' => $request->input('default_brand'),
                'preview_only' => $request->boolean('preview_only', false),
                'created_by' => auth()->id(),
            ];
            
            \Log::info('XML Import: Processing file', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'config' => $config
            ]);
            
            $results = $this->xmlImportService->importFromXml($file, $config);
            
            \Log::info('XML Import: Completed successfully', ['results' => $results]);
            
            return response()->json([
                'success' => true,
                'message' => 'Products imported from XML successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            \Log::error('XML Import Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'XML product import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download XML template
     * GET /api/admin/products/xml-template
     */
    public function downloadXmlTemplate(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $type = $request->get('type', 'custom');
            
            if ($type === 'google_merchant') {
                $templateContent = $this->xmlImportService->generateGoogleMerchantTemplate();
                $filename = 'google_merchant_template.xml';
            } else {
                $templateContent = $this->xmlImportService->generateXmlTemplate();
                $filename = 'custom_xml_template.xml';
            }
            
            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'xml_template_');
            file_put_contents($tempFile, $templateContent);
            
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate XML template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data
     * POST /api/admin/products/import/preview
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,txt|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $preview = $this->importExportService->previewImport($file);
            
            return response()->json([
                'success' => true,
                'data' => $preview
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
     * Validate import file
     * POST /api/admin/products/import/validate
     */
    public function validateImportFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,txt|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $validation = $this->importExportService->validateImportFile($file);
            
            return response()->json([
                'success' => true,
                'data' => $validation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import file validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download import template
     * GET /api/admin/products/import/template
     */
    public function importTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        try {
            $templatePath = $this->importExportService->getTemplatePath();
            return response()->download($templatePath, 'product_import_template.csv');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate import template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export products
     * GET /api/admin/products/export
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
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
                'featured'
            ]);
            
            $filename = 'products_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $filePath = $this->importExportService->exportToFile($filters, $filename);
            
            return response()->download($filePath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}