<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use App\Models\Product;
use App\Models\Category;
use App\Models\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportController extends BaseController
{
    /**
     * Analyze uploaded XML file and return product count
     */
    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'xml_file' => 'required|file|mimes:xml|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        try {
            $file = $request->file('xml_file');
            $seller = Auth::user();
            
            // Store the file temporarily
            $filename = 'imports/' . $seller->id . '/' . time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('temp', $filename);
            
            // Parse XML to count products
            $xmlContent = Storage::get('temp/' . $filename);
            $productCount = $this->countProductsInXML($xmlContent);
            
            // Create import job record
            $importJob = ImportJob::create([
                'user_id' => $seller->id,
                'filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'total_products' => $productCount,
                'status' => 'pending',
                'file_id' => Str::uuid(),
            ]);

            return $this->sendResponse([
                'file_id' => $importJob->file_id,
                'product_count' => $productCount,
                'filename' => $file->getClientOriginalName(),
            ], 'File analyzed successfully');

        } catch (\Exception $e) {
            Log::error('XML analysis error: ' . $e->getMessage());
            return $this->sendError('Failed to analyze XML file: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Process the XML import
     */
    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        try {
            $seller = Auth::user();
            $importJob = ImportJob::where('file_id', $request->file_id)
                                  ->where('user_id', $seller->id)
                                  ->first();

            if (!$importJob) {
                return $this->sendError('Import job not found', [], 404);
            }

            // Update job status to processing
            $importJob->update(['status' => 'processing']);

            // Start processing in background (you might want to use a queue for this)
            $this->processXMLFile($importJob);

            return $this->sendResponse([
                'job_id' => $importJob->file_id,
                'message' => 'Import process started'
            ], 'Import started successfully');

        } catch (\Exception $e) {
            Log::error('XML processing error: ' . $e->getMessage());
            return $this->sendError('Failed to start import process: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get import job status
     */
    public function status($jobId)
    {
        try {
            $seller = Auth::user();
            $importJob = ImportJob::where('file_id', $jobId)
                                  ->where('user_id', $seller->id)
                                  ->first();

            if (!$importJob) {
                return $this->sendError('Import job not found', [], 404);
            }

            return $this->sendResponse([
                'processed' => $importJob->processed_products,
                'total' => $importJob->total_products,
                'completed' => $importJob->status === 'completed',
                'results' => $importJob->results ? json_decode($importJob->results, true) : null,
            ], 'Status retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Status check error: ' . $e->getMessage());
            return $this->sendError('Failed to get import status: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Count products in XML file
     */
    private function countProductsInXML($xmlContent)
    {
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            throw new \Exception('Invalid XML format');
        }

        // Register namespace for Google Shopping
        $xml->registerXPathNamespace('g', 'http://base.google.com/ns/1.0');
        
        // Count items
        $items = $xml->xpath('//item');
        return count($items);
    }

    /**
     * Process XML file and import products
     */
    private function processXMLFile($importJob)
    {
        try {
            $xmlContent = Storage::get($importJob->file_path);
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                throw new \Exception('Invalid XML format');
            }

            // Register namespace for Google Shopping
            $xml->registerXPathNamespace('g', 'http://base.google.com/ns/1.0');
            
            $items = $xml->xpath('//item');
            $successCount = 0;
            $updateCount = 0;
            $failCount = 0;
            $errors = [];
            $processed = 0;

            foreach ($items as $item) {
                try {
                    $productData = $this->parseGoogleMerchantItem($item);
                    
                    if ($this->importProduct($productData, $importJob->user_id)) {
                        // Check if product already existed
                        $existingProduct = Product::where('sku', $productData['sku'])
                                                ->where('seller_id', $importJob->user_id)
                                                ->first();
                        if ($existingProduct && $existingProduct->wasRecentlyCreated === false) {
                            $updateCount++;
                        } else {
                            $successCount++;
                        }
                    } else {
                        $failCount++;
                        $errors[] = "Failed to import product: " . ($productData['title'] ?? 'Unknown');
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = "Error processing item: " . $e->getMessage();
                }
                
                $processed++;
                
                // Update progress
                $importJob->update(['processed_products' => $processed]);
                
                // Add small delay to prevent overwhelming the system
                usleep(100000); // 0.1 second
            }

            // Update final results
            $results = [
                'success' => $successCount,
                'updated' => $updateCount,
                'failed' => $failCount,
                'errors' => array_slice($errors, 0, 10), // Limit errors to first 10
            ];

            $importJob->update([
                'status' => 'completed',
                'processed_products' => $processed,
                'results' => json_encode($results),
            ]);

            // Clean up temporary file
            Storage::delete($importJob->file_path);

        } catch (\Exception $e) {
            Log::error('XML processing error: ' . $e->getMessage());
            $importJob->update([
                'status' => 'failed',
                'results' => json_encode(['error' => $e->getMessage()]),
            ]);
        }
    }

    /**
     * Parse Google Merchant Center XML item
     */
    private function parseGoogleMerchantItem($item)
    {
        // Register namespace
        $namespaces = $item->getNamespaces(true);
        $g = $item->children($namespaces['g'] ?? 'http://base.google.com/ns/1.0');
        
        return [
            'sku' => (string) $g->id,
            'title' => (string) $g->title,
            'description' => (string) $g->description,
            'price' => $this->parsePrice((string) $g->price),
            'sale_price' => $this->parsePrice((string) $g->sale_price),
            'availability' => (string) $g->availability,
            'condition' => (string) $g->condition,
            'brand' => (string) $g->brand,
            'gtin' => (string) $g->gtin,
            'mpn' => (string) $g->mpn,
            'product_type' => (string) $g->product_type,
            'google_product_category' => (string) $g->google_product_category,
            'image_link' => (string) $g->image_link,
            'additional_image_link' => (string) $g->additional_image_link,
            'link' => (string) $g->link,
            'weight' => (string) $g->shipping_weight,
            'shipping' => (string) $g->shipping,
        ];
    }

    /**
     * Parse price from Google Merchant format (e.g., "29.99 USD")
     */
    private function parsePrice($priceString)
    {
        if (empty($priceString)) {
            return null;
        }
        
        // Extract numeric value from price string
        preg_match('/[\d.]+/', $priceString, $matches);
        return isset($matches[0]) ? (float) $matches[0] : null;
    }

    /**
     * Import a single product
     */
    private function importProduct($productData, $sellerId)
    {
        try {
            // Find or create category
            $category = $this->findOrCreateCategory($productData['product_type']);
            
            // Check if product already exists
            $product = Product::where('sku', $productData['sku'])
                             ->where('seller_id', $sellerId)
                             ->first();

            $data = [
                'seller_id' => $sellerId,
                'category_id' => $category->id,
                'name' => $productData['title'],
                'description' => $productData['description'],
                'sku' => $productData['sku'],
                'price' => $productData['price'],
                'sale_price' => $productData['sale_price'],
                'brand' => $productData['brand'],
                'gtin' => $productData['gtin'],
                'mpn' => $productData['mpn'],
                'availability' => $this->mapAvailability($productData['availability']),
                'condition' => $this->mapCondition($productData['condition']),
                'image_url' => $productData['image_link'],
                'additional_images' => $productData['additional_image_link'] ? [$productData['additional_image_link']] : [],
                'external_url' => $productData['link'],
                'weight' => $this->parseWeight($productData['weight']),
                'is_active' => true,
                'google_category' => $productData['google_product_category'],
                'import_source' => 'google_merchant',
                'imported_at' => now(),
            ];

            if ($product) {
                // Update existing product
                $product->update($data);
            } else {
                // Create new product
                $data['slug'] = Str::slug($productData['title']) . '-' . Str::random(6);
                $product = Product::create($data);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Product import error: ' . $e->getMessage(), $productData);
            return false;
        }
    }

    /**
     * Find or create category
     */
    private function findOrCreateCategory($categoryName)
    {
        if (empty($categoryName)) {
            // Return default category
            return Category::firstOrCreate(
                ['name' => 'Uncategorized'],
                ['slug' => 'uncategorized', 'is_active' => true]
            );
        }

        return Category::firstOrCreate(
            ['name' => $categoryName],
            ['slug' => Str::slug($categoryName), 'is_active' => true]
        );
    }

    /**
     * Map Google Merchant availability to our system
     */
    private function mapAvailability($availability)
    {
        $mapping = [
            'in stock' => 'in_stock',
            'out of stock' => 'out_of_stock',
            'preorder' => 'preorder',
            'backorder' => 'backorder',
        ];

        return $mapping[strtolower($availability)] ?? 'out_of_stock';
    }

    /**
     * Map Google Merchant condition to our system
     */
    private function mapCondition($condition)
    {
        $mapping = [
            'new' => 'new',
            'refurbished' => 'refurbished',
            'used' => 'used',
        ];

        return $mapping[strtolower($condition)] ?? 'new';
    }

    /**
     * Parse weight from string
     */
    private function parseWeight($weightString)
    {
        if (empty($weightString)) {
            return null;
        }
        
        preg_match('/[\d.]+/', $weightString, $matches);
        return isset($matches[0]) ? (float) $matches[0] : null;
    }
}