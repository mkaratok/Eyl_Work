<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\PriceService;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PriceController extends Controller
{
    protected PriceService $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
        $this->middleware('auth:seller');
    }

    /**
     * Get seller's product prices
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status'); // active, inactive, out_of_stock

            $query = ProductPrice::where('seller_id', $sellerId)
                ->with(['product'])
                ->orderBy('updated_at', 'desc');

            // Apply search filter
            if ($search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            switch ($status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'out_of_stock':
                    $query->where('stock', '<=', 0);
                    break;
            }

            $prices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $prices->items(),
                'meta' => [
                    'current_page' => $prices->currentPage(),
                    'last_page' => $prices->lastPage(),
                    'per_page' => $prices->perPage(),
                    'total' => $prices->total(),
                    'from' => $prices->firstItem(),
                    'to' => $prices->lastItem()
                ],
                'message' => 'Product prices retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product prices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product price
     */
    public function updatePrice(Request $request, int $productId): JsonResponse
    {
        try {
            $sellerId = Auth::id();

            // Validate request
            $validated = $request->validate([
                'price' => 'required|numeric|min:0.01|max:999999.99',
                'stock' => 'nullable|integer|min:0|max:999999',
                'is_active' => 'nullable|boolean'
            ]);

            // Check if seller owns this product or has permission to set price
            $product = Product::findOrFail($productId);
            
            // Verify seller can set price for this product
            if (!$this->canSellerSetPrice($sellerId, $productId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to set price for this product'
                ], 403);
            }

            // Validate price data
            $errors = $this->priceService->validatePriceData([
                'price' => $validated['price'],
                'stock' => $validated['stock'] ?? null,
                'product_id' => $productId
            ]);

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }

            // Update price
            $productPrice = $this->priceService->updatePrice(
                $productId,
                $sellerId,
                $validated['price'],
                $validated['stock'] ?? null
            );

            // Update active status if provided
            if (isset($validated['is_active'])) {
                $productPrice->update(['is_active' => $validated['is_active']]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $productPrice->id,
                    'product_id' => $productPrice->product_id,
                    'seller_id' => $productPrice->seller_id,
                    'price' => $productPrice->price,
                    'formatted_price' => $productPrice->formatted_price,
                    'stock' => $productPrice->stock,
                    'is_active' => $productPrice->is_active,
                    'is_in_stock' => $productPrice->is_in_stock,
                    'updated_at' => $productPrice->updated_at->format('Y-m-d H:i:s')
                ],
                'message' => 'Product price updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product price: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update prices
     */
    public function bulkUpdatePrices(Request $request): JsonResponse
    {
        try {
            $sellerId = Auth::id();

            // Validate request
            $validated = $request->validate([
                'updates' => 'required|array|min:1|max:100',
                'updates.*.product_id' => 'required|integer|exists:products,id',
                'updates.*.price' => 'required|numeric|min:0.01|max:999999.99',
                'updates.*.stock' => 'nullable|integer|min:0|max:999999'
            ]);

            // Verify seller can update all products
            $productIds = collect($validated['updates'])->pluck('product_id');
            foreach ($productIds as $productId) {
                if (!$this->canSellerSetPrice($sellerId, $productId)) {
                    return response()->json([
                        'success' => false,
                        'message' => "You do not have permission to set price for product ID: {$productId}"
                    ], 403);
                }
            }

            // Perform bulk update
            $result = $this->priceService->bulkUpdatePrices($sellerId, $validated['updates']);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Bulk price update completed. {$result['success_count']} successful, {$result['error_count']} failed."
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk price update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seller's price performance
     */
    public function getPerformance(Request $request): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            $days = $request->get('days', 30);

            if ($days < 1 || $days > 365) {
                return response()->json([
                    'success' => false,
                    'message' => 'Days parameter must be between 1 and 365'
                ], 400);
            }

            $performance = $this->priceService->getSellerPricePerformance($sellerId, $days);

            return response()->json([
                'success' => true,
                'data' => [
                    'seller_id' => $sellerId,
                    'days' => $days,
                    'performance' => $performance
                ],
                'message' => 'Price performance retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get price history for seller's product
     */
    public function getPriceHistory(Request $request, int $productId): JsonResponse
    {
        try {
            $sellerId = Auth::id();

            // Verify seller owns this product
            if (!$this->canSellerSetPrice($sellerId, $productId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this product\'s price history'
                ], 403);
            }

            $days = $request->get('days', 30);
            $history = $this->priceService->getPriceHistory($productId, $days);

            // Filter history to show only this seller's changes
            $sellerHistory = collect($history)->filter(function ($item) use ($sellerId) {
                // This would need seller info in the history data
                // For now, return all history but we could enhance this
                return true;
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'seller_id' => $sellerId,
                    'days' => $days,
                    'history' => $sellerHistory,
                    'total_records' => count($sellerHistory)
                ],
                'message' => 'Price history retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle product price active status
     */
    public function toggleActive(int $productId): JsonResponse
    {
        try {
            $sellerId = Auth::id();

            $productPrice = ProductPrice::where('product_id', $productId)
                ->where('seller_id', $sellerId)
                ->firstOrFail();

            $productPrice->update([
                'is_active' => !$productPrice->is_active
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $productPrice->id,
                    'is_active' => $productPrice->is_active
                ],
                'message' => 'Product price status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle product price status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get price comparison for seller's product
     */
    public function getComparison(int $productId): JsonResponse
    {
        try {
            $sellerId = Auth::id();

            // Verify seller has this product
            if (!$this->canSellerSetPrice($sellerId, $productId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this product\'s price comparison'
                ], 403);
            }

            $comparison = $this->priceService->getPriceComparison($productId);
            
            // Highlight seller's price in the comparison
            foreach ($comparison['prices'] as &$price) {
                $price['is_my_price'] = $price['seller_id'] == $sellerId;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'seller_id' => $sellerId,
                    'comparison' => $comparison
                ],
                'message' => 'Price comparison retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve price comparison: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export seller's prices to Excel/CSV
     */
    public function exportPrices(Request $request): JsonResponse
    {
        try {
            $sellerId = Auth::id();
            $format = $request->get('format', 'xlsx'); // xlsx, csv

            if (!in_array($format, ['xlsx', 'csv'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid format. Supported formats: xlsx, csv'
                ], 400);
            }

            $prices = ProductPrice::where('seller_id', $sellerId)
                ->with(['product'])
                ->get();

            $exportData = $prices->map(function ($price) {
                return [
                    'Product ID' => $price->product_id,
                    'Product Name' => $price->product->name ?? 'N/A',
                    'Brand' => $price->product->brand ?? 'N/A',
                    'SKU' => $price->product->sku ?? 'N/A',
                    'Price' => $price->price,
                    'Stock' => $price->stock,
                    'Active' => $price->is_active ? 'Yes' : 'No',
                    'In Stock' => $price->is_in_stock ? 'Yes' : 'No',
                    'Last Updated' => $price->updated_at->format('Y-m-d H:i:s')
                ];
            })->toArray();

            // Generate filename
            $filename = 'seller_prices_' . $sellerId . '_' . date('Y-m-d_H-i-s') . '.' . $format;
            $filePath = storage_path('app/exports/' . $filename);

            // Create exports directory if it doesn't exist
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            // Export based on format
            if ($format === 'csv') {
                $this->exportToCsv($exportData, $filePath);
            } else {
                $this->exportToExcel($exportData, $filePath);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'download_url' => url('api/v1/seller/prices/download/' . $filename),
                    'total_records' => count($exportData)
                ],
                'message' => 'Prices exported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export prices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if seller can set price for a product
     */
    private function canSellerSetPrice(int $sellerId, int $productId): bool
    {
        // Check if seller created the product OR has existing price entry
        $product = Product::find($productId);
        if (!$product) {
            return false;
        }

        // If seller created the product, they can set price
        if ($product->created_by == $sellerId) {
            return true;
        }

        // If seller has existing price entry, they can update it
        $existingPrice = ProductPrice::where('product_id', $productId)
            ->where('seller_id', $sellerId)
            ->exists();

        return $existingPrice;
    }

    /**
     * Export data to CSV
     */
    private function exportToCsv(array $data, string $filePath): void
    {
        $file = fopen($filePath, 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }
        
        fclose($file);
    }

    /**
     * Export data to Excel (simplified version)
     */
    private function exportToExcel(array $data, string $filePath): void
    {
        // This is a simplified implementation
        // In production, you'd use Laravel Excel package
        $this->exportToCsv($data, $filePath);
    }
}
