<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Requests\XmlProductImportRequest;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\ProductImportExportService;
use App\Services\XmlProductImportService;
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
        $this->middleware(['auth:api', 'role:seller']);
    }

    /**
     * Get seller's products
     * GET /api/seller/products
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
                'sort_by',
                'sort_direction'
            ]);

            // Only show products created by this seller
            $sellerId = Auth::id();
            
            $perPage = min($request->get('per_page', 15), 50);
            
            $query = Product::with(['category', 'activePrices'])
                ->where('created_by', $sellerId);

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
                    $query->where('admin_approved', false);
                }
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortDirection = $filters['sort_direction'] ?? 'desc';
            $query->orderBy($sortBy, $sortDirection);

            $products = $query->paginate($perPage);

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
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single product for seller
     * GET /api/seller/products/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            
            $product = Product::with([
                'category',
                'activePrices',
                'priceHistory' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                }
            ])
            ->where('created_by', $sellerId)
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or access denied'
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
     * Create a new product
     * POST /api/seller/products
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $sellerId = Auth::id();
            
            $product = $this->productService->createProduct($data, $sellerId);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load(['category', 'activePrices'])
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
     * Update a product
     * PUT /api/seller/products/{id}
     */
    public function update($id, ProductUpdateRequest $request): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            
            $product = Product::where('created_by', $sellerId)->findOrFail($id);
            $data = $request->validated();
            
            $updatedProduct = $this->productService->updateProduct($product, $data);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $updatedProduct->load(['category', 'activePrices'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or access denied'
            ], 404);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

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
     * DELETE /api/seller/products/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            
            $product = Product::where('created_by', $sellerId)->findOrFail($id);
            
            // Only allow deletion if not approved yet
            if ($product->admin_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete approved products. Contact admin for removal.'
                ], 403);
            }
            
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
                'message' => 'Product not found or access denied'
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
     * Duplicate a product
     * POST /api/seller/products/{id}/duplicate
     */
    public function duplicate($id): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            
            $product = Product::where('created_by', $sellerId)->findOrFail($id);
            $duplicate = $product->duplicate();

            return response()->json([
                'success' => true,
                'message' => 'Product duplicated successfully',
                'data' => $duplicate->load(['category'])
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or access denied'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit product for approval
     * POST /api/seller/products/{id}/submit
     */
    public function submit($id): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            
            $product = Product::where('created_by', $sellerId)->findOrFail($id);
            
            if ($product->admin_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is already approved'
                ], 400);
            }

            if ($product->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is already submitted for approval'
                ], 400);
            }

            // Validate product has required fields
            if (empty($product->name) || empty($product->category_id) || empty($product->description)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product must have name, category, and description before submission'
                ], 422);
            }

            $product->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Product submitted for approval successfully',
                'data' => $product->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or access denied'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seller's product statistics
     * GET /api/seller/products/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            
            $stats = [
                'total' => Product::where('created_by', $sellerId)->count(),
                'published' => Product::where('created_by', $sellerId)->published()->count(),
                'pending' => Product::where('created_by', $sellerId)->pending()->count(),
                'approved' => Product::where('created_by', $sellerId)->approved()->count(),
                'rejected' => Product::where('created_by', $sellerId)->where('status', 'rejected')->count(),
                'draft' => Product::where('created_by', $sellerId)->where('status', 'draft')->count(),
                'featured' => Product::where('created_by', $sellerId)->featured()->count(),
            ];

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
     * Upload product images
     * POST /api/seller/products/{id}/images
     */
    public function uploadImages($id, Request $request): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120' // 5MB max
        ]);

        try {
            $sellerId = Auth::id();
            
            $product = Product::where('created_by', $sellerId)->findOrFail($id);
            $uploadedImages = $request->file('images');
            
            $existingImages = $product->images ?? [];
            $newImages = $this->productService->processImages($uploadedImages, $existingImages);
            
            $product->update(['images' => $newImages]);

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => [
                    'images' => $newImages,
                    'image_urls' => $product->fresh()->image_urls
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or access denied'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove product image
     * DELETE /api/seller/products/{id}/images
     */
    public function removeImage($id, Request $request): JsonResponse
    {
        $request->validate([
            'image_path' => 'required|string'
        ]);

        try {
            $sellerId = Auth::id();
            
            $product = Product::where('created_by', $sellerId)->findOrFail($id);
            $imagePath = $request->get('image_path');
            
            $product->removeImage($imagePath);
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Image removed successfully',
                'data' => [
                    'images' => $product->images,
                    'image_urls' => $product->image_urls
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or access denied'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder product images
     * PUT /api/seller/products/{id}/images/reorder
     */
    public function reorderImages($id, Request $request): JsonResponse
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'string'
        ]);

        try {
            $sellerId = Auth::id();
            
            $product = Product::where('created_by', $sellerId)->findOrFail($id);
            $orderedImages = $request->get('images');
            
            $product->reorderImages($orderedImages);
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Images reordered successfully',
                'data' => [
                    'images' => $product->images,
                    'image_urls' => $product->image_urls
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or access denied'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import products from XML file
     * POST /api/seller/products/import-xml
     */
    public function importXml(XmlProductImportRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $config = $request->getImportConfig();
            
            // Set seller as creator
            $config['created_by'] = Auth::id();
            // Sellers cannot auto-approve their products
            $config['auto_approve'] = false;

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
     * GET /api/seller/products/xml-template
     */
    public function downloadXmlTemplate(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $type = $request->get('type', 'custom'); // custom or google_merchant
            
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
     * Export seller's products to Excel/CSV
     * GET /api/seller/products/export
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            
            $filters = $request->only([
                'status',
                'category_id',
                'brand',
                'created_from',
                'created_to'
            ]);

            // Add seller filter
            $filters['created_by'] = $sellerId;

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
}
