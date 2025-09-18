<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class XmlProductImportService
{
    protected $productService;
    protected $results;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
        $this->results = [
            'total_products' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => []
        ];
    }

    /**
     * Import products from XML file
     */
    public function importFromXml(UploadedFile $file, array $config = []): array
    {
        try {
            Log::info('Starting XML import process', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'config' => $config
            ]);
            
            // Reset results
            $this->results = [
                'total_products' => 0,
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
                'warnings' => []
            ];
            
            // Validate XML file
            $this->validateXmlFile($file);
            
            // Parse XML content
            $xmlContent = file_get_contents($file->getRealPath());
            Log::info('XML content loaded', ['content_length' => strlen($xmlContent)]);
            
            // Check if content is valid XML
            if (empty(trim($xmlContent))) {
                throw new \Exception('XML file is empty');
            }
            
            $xml = new SimpleXMLElement($xmlContent);
            
            // Detect XML format (Google Shopping, Custom, etc.)
            $format = $this->detectXmlFormat($xml);
            Log::info('XML format detected', ['format' => $format]);
            
            // Process products based on format
            switch ($format) {
                case 'google_merchant':
                    return $this->processGoogleMerchantXml($xml, $config);
                case 'custom':
                    return $this->processCustomXml($xml, $config);
                default:
                    throw new \Exception('Unsupported XML format. Supported formats: Google Merchant Center, Custom XML');
            }

        } catch (\Exception $e) {
            Log::error('XML import failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process Google Merchant Center XML format
     */
    protected function processGoogleMerchantXml(SimpleXMLElement $xml, array $config): array
    {
        // Register namespaces if they exist
        $namespaces = $xml->getNamespaces(true);
        
        // Register all namespaces for XPath queries
        foreach ($namespaces as $prefix => $uri) {
            if ($prefix === '') {
                // Default namespace - register with a prefix for XPath
                $xml->registerXPathNamespace('default', $uri);
            } else {
                $xml->registerXPathNamespace($prefix, $uri);
            }
        }
        
        // Try different XPath queries to find items, including Atom namespace
        $products = [];
        $xpathQueries = [
            '//default:item',  // Atom namespace with default prefix
            '//item',          // Standard item
            '//channel/item',  // RSS channel
            '//feed/item',     // Feed item
            '/feed/entry',     // Feed entry
            '/rss/channel/item' // RSS channel item
        ];
        
        foreach ($xpathQueries as $query) {
            $items = $xml->xpath($query);
            if (!empty($items)) {
                $products = $items;
                Log::info('Found products using query: ' . $query, ['count' => count($items)]);
                break;
            }
        }
        
        if (empty($products)) {
            Log::warning('No products found in XML');
            $this->results['errors'][] = 'No products found in XML file';
            return $this->results;
        }
        
        $this->results['total_products'] = count($products);
        Log::info('Processing Google Merchant XML', ['product_count' => count($products)]);
        
        foreach ($products as $index => $productXml) {
            try {
                // Register all namespaces for each product element
                foreach ($namespaces as $prefix => $uri) {
                    if ($prefix === '') {
                        // Default namespace - register with a prefix for XPath
                        $productXml->registerXPathNamespace('default', $uri);
                    } else {
                        $productXml->registerXPathNamespace($prefix, $uri);
                    }
                }
                
                $productData = $this->parseGoogleMerchantProduct($productXml, $config);
                $this->processProductData($productData, $config);
                $this->results['processed']++;
                
                // Log progress every 10 products
                if (($index + 1) % 10 === 0) {
                    Log::info('XML import progress', [
                        'processed' => $index + 1,
                        'total' => count($products)
                    ]);
                }
                
            } catch (\Exception $e) {
                $errorMessage = "Product {$index}: " . $e->getMessage();
                $this->results['errors'][] = $errorMessage;
                Log::warning('Failed to process product', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info('Google Merchant XML processing completed', $this->results);
        return $this->results;
    }

    /**
     * Parse Google Merchant Center product data
     */
    protected function parseGoogleMerchantProduct(SimpleXMLElement $productXml, array $config): array
    {
        // Get namespaces
        $namespaces = $productXml->getNamespaces(true);
        $hasGNamespace = isset($namespaces['g']);
        
        // Register namespaces for the product element
        foreach ($namespaces as $prefix => $uri) {
            if ($prefix === '') {
                // Default namespace - register with a prefix for XPath
                $productXml->registerXPathNamespace('default', $uri);
            } else {
                $productXml->registerXPathNamespace($prefix, $uri);
            }
        }
        
        // Helper function to get value with namespace support
        $getValue = function($element, $field, $gField = null) use ($hasGNamespace, $namespaces) {
            // Try standard field
            $value = (string) $element->$field;
            
            // If empty and we have g namespace, try with g: prefix
            if (empty($value) && $hasGNamespace && $gField) {
                $gValue = $element->children('g', true)->$gField;
                $value = (string) $gValue;
            }
            
            // If still empty, try with CDATA (common in Google feeds)
            if (empty($value) && $gField) {
                // Try to get value using XPath for better namespace handling
                $xpathResult = $element->xpath(".//g:{$gField}");
                if (!empty($xpathResult)) {
                    $value = (string) $xpathResult[0];
                }
            }
            
            // Handle CDATA content
            if (empty($value)) {
                $children = $element->$field;
                if ($children && $children->count() > 0) {
                    $value = (string) $children;
                }
            }
            
            return $value;
        };
        
        // Map Google Merchant fields to our product structure
        $data = [
            'name' => $getValue($productXml, 'title', 'title'),
            'description' => $getValue($productXml, 'description', 'description'),
            'sku' => $getValue($productXml, 'id', 'id'),
            'barcode' => $getValue($productXml, 'gtin', 'gtin'),
            'brand' => $getValue($productXml, 'brand', 'brand'),
            'price' => $this->parsePrice($getValue($productXml, 'price', 'price')),
            'image_url' => $getValue($productXml, 'image_link', 'image_link'),
            'availability' => $this->mapAvailability($getValue($productXml, 'availability', 'availability')),
            'condition' => $this->mapCondition($getValue($productXml, 'condition', 'condition')),
            'google_category' => $getValue($productXml, 'google_product_category', 'google_product_category'),
            'external_url' => $getValue($productXml, 'link', 'link'),
            'mpn' => $getValue($productXml, 'mpn', 'mpn'),
            'weight' => $this->parseWeight($getValue($productXml, 'shipping_weight', 'shipping_weight')),
        ];
        
        // Handle additional images
        $additionalImages = [];
        
        // Try standard namespace
        if ($productXml->additional_image_link) {
            foreach ($productXml->additional_image_link as $imageLink) {
                $additionalImages[] = (string) $imageLink;
            }
        }
        
        // Try g namespace
        if ($hasGNamespace && $productXml->children('g', true)->additional_image_link) {
            foreach ($productXml->children('g', true)->additional_image_link as $imageLink) {
                $additionalImages[] = (string) $imageLink;
            }
        }
        
        // Try XPath for additional images (better namespace handling)
        $additionalImageLinks = $productXml->xpath('.//g:additional_image_link');
        if (!empty($additionalImageLinks)) {
            foreach ($additionalImageLinks as $imageLink) {
                $imageUrl = (string) $imageLink;
                if (!empty($imageUrl) && !in_array($imageUrl, $additionalImages)) {
                    $additionalImages[] = $imageUrl;
                }
            }
        }
        
        $data['additional_images'] = $additionalImages;
        
        // Map category
        $data['category_id'] = $this->mapCategory(
            $getValue($productXml, 'product_type', 'product_type'),
            $getValue($productXml, 'google_product_category', 'google_product_category'),
            $config
        );
        
        // Set import metadata
        $data['import_source'] = 'google_merchant_xml';
        $data['imported_at'] = now();
        $data['status'] = $config['auto_approve'] ?? false ? 'published' : 'pending';
        $data['admin_approved'] = $config['auto_approve'] ?? false;
        $data['created_by'] = $config['created_by'] ?? null;
        
        // Filter out empty values
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
        
        Log::debug('Parsed Google Merchant product data', $data);
        return $data;
    }

    /**
     * Process custom XML format
     */
    protected function processCustomXml(SimpleXMLElement $xml, array $config): array
    {
        $products = $xml->xpath('//product') ?: [];
        $this->results['total_products'] = count($products);
        
        Log::info('Processing Custom XML', ['product_count' => count($products)]);
        
        foreach ($products as $index => $productXml) {
            try {
                $productData = $this->parseCustomProduct($productXml, $config);
                $this->processProductData($productData, $config);
                $this->results['processed']++;
                
                // Log progress every 10 products
                if (($index + 1) % 10 === 0) {
                    Log::info('XML import progress', [
                        'processed' => $index + 1,
                        'total' => count($products)
                    ]);
                }
                
            } catch (\Exception $e) {
                $errorMessage = "Product {$index}: " . $e->getMessage();
                $this->results['errors'][] = $errorMessage;
                Log::warning('Failed to process product', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info('Custom XML processing completed', $this->results);
        return $this->results;
    }

    /**
     * Parse custom XML product data
     */
    protected function parseCustomProduct(SimpleXMLElement $productXml, array $config): array
    {
        $data = [
            'name' => (string) $productXml->name,
            'description' => (string) $productXml->description,
            'sku' => (string) $productXml->sku,
            'barcode' => (string) $productXml->barcode,
            'brand' => (string) $productXml->brand,
            'price' => $this->parsePrice((string) $productXml->price),
            'image_url' => (string) $productXml->image,
            'category_id' => $this->mapCategory((string) $productXml->category, '', $config),
        ];

        // Set defaults
        $data['import_source'] = 'custom_xml';
        $data['imported_at'] = now();
        $data['status'] = $config['auto_approve'] ?? false ? 'published' : 'pending';
        $data['admin_approved'] = $config['auto_approve'] ?? false;
        $data['created_by'] = $config['created_by'] ?? null;

        // Filter out empty values
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '' && $value !== [];
        });

        Log::debug('Parsed Custom XML product data', $data);
        return $data;
    }

    /**
     * Process individual product data
     */
    protected function processProductData(array $productData, array $config)
    {
        DB::beginTransaction();
        
        try {
            // Validate required fields
            if (empty($productData['name'])) {
                throw new \Exception('Product name is required');
            }

            // Check for existing product
            $existing = null;
            if (!empty($productData['sku'])) {
                $existing = Product::where('sku', $productData['sku'])->first();
            } elseif (!empty($productData['barcode'])) {
                $existing = Product::where('barcode', $productData['barcode'])->first();
            }

            if ($existing) {
                // Update existing product
                if ($config['update_existing'] ?? true) {
                    Log::info('Updating existing product', [
                        'product_id' => $existing->id,
                        'name' => $productData['name']
                    ]);
                    $this->productService->updateProduct($existing, $productData);
                    $this->results['updated']++;
                } else {
                    $this->results['skipped']++;
                    $warningMessage = "Product skipped: {$productData['name']} (already exists)";
                    $this->results['warnings'][] = $warningMessage;
                    Log::info($warningMessage);
                }
            } else {
                // Create new product
                Log::info('Creating new product', ['name' => $productData['name']]);
                $this->productService->createProduct($productData, $config['created_by'] ?? null);
                $this->results['created']++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process product data', [
                'error' => $e->getMessage(),
                'data' => $productData,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Detect XML format
     */
    protected function detectXmlFormat(SimpleXMLElement $xml): string
    {
        // Register namespaces if they exist
        $namespaces = $xml->getNamespaces(true);
        
        // Register all namespaces for XPath queries
        foreach ($namespaces as $prefix => $uri) {
            if ($prefix === '') {
                // Default namespace - register with a prefix for XPath
                $xml->registerXPathNamespace('default', $uri);
            } else {
                $xml->registerXPathNamespace($prefix, $uri);
            }
        }
        
        // Check for Google Merchant Center format with Atom namespace structure
        // This handles the specific format in your XML file
        if ($xml->xpath('//default:feed') && $xml->xpath('//default:item')) {
            return 'google_merchant';
        }
        
        // Check for standard Google Merchant Center format
        if ($xml->xpath('//feed') || $xml->xpath('//channel') || $xml->xpath('//rss')) {
            // Check if it has Google product elements
            if ($xml->xpath('//item') || $xml->xpath('//entry')) {
                // Check for Google-specific elements
                if ($xml->xpath('//item/g:id') || $xml->xpath('//item/g:title') || 
                    $xml->xpath('//item/g:price') || $xml->xpath('//item/g:availability')) {
                    return 'google_merchant';
                }
                
                // If we have items but no clear format, assume Google Merchant
                return 'google_merchant';
            }
        }
        
        // Check for Google Merchant Center format with g: namespace
        if ($xml->xpath('//item/g:id') || $xml->xpath('//item/g:title') || $xml->xpath('//item/g:price')) {
            return 'google_merchant';
        }
        
        // Check for custom format
        if ($xml->xpath('//product/name') || $xml->xpath('//product/sku') || $xml->xpath('//product/price')) {
            return 'custom';
        }
        
        // If we have items but couldn't determine the format, default to Google Merchant
        if ($xml->xpath('//item') || $xml->xpath('//entry')) {
            return 'google_merchant';
        }
        
        return 'unknown';
    }

    /**
     * Validate XML file
     */
    protected function validateXmlFile(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        if ($file->getSize() > 50 * 1024 * 1024) { // 50MB limit
            throw new \Exception('File too large. Maximum size is 50MB');
        }

        // Check file extension first
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'xml') {
            throw new \Exception('Invalid file extension. Only XML files are allowed');
        }

        // Be more lenient with MIME types
        $allowedMimes = [
            'application/xml', 
            'text/xml', 
            'text/plain',  // Some systems save XML as text/plain
            'application/octet-stream' // Generic binary type
        ];
        
        $mimeType = $file->getMimeType();
        Log::info('XML file MIME type', ['mime_type' => $mimeType, 'file' => $file->getClientOriginalName()]);
        
        if (!in_array($mimeType, $allowedMimes)) {
            // Try to validate content instead of relying only on MIME type
            try {
                $content = file_get_contents($file->getRealPath());
                if (empty($content)) {
                    throw new \Exception('File is empty');
                }
                
                // Check if content starts with XML declaration or has XML-like structure
                if (strpos($content, '<?xml') !== false || 
                    strpos($content, '<feed') !== false || 
                    strpos($content, '<rss') !== false || 
                    strpos($content, '<products') !== false) {
                    // Looks like XML, proceed
                    Log::info('File appears to be XML based on content inspection');
                } else {
                    throw new \Exception('File does not appear to be valid XML');
                }
            } catch (\Exception $e) {
                if ($e->getMessage() === 'File does not appear to be valid XML') {
                    throw $e;
                }
                throw new \Exception('Invalid file type. Only XML files are allowed');
            }
        }
    }

    /**
     * Helper methods for data parsing
     */
    protected function parsePrice(string $price): ?float
    {
        if (empty($price)) return null;
        
        // Remove currency symbols and spaces
        $cleanPrice = preg_replace('/[^\d.,]/', '', $price);
        $cleanPrice = str_replace(',', '.', $cleanPrice);
        
        return is_numeric($cleanPrice) ? (float) $cleanPrice : null;
    }

    protected function parseWeight(string $weight): ?float
    {
        if (empty($weight)) return null;
        
        // Extract numeric value (assume kg if no unit specified)
        preg_match('/[\d.]+/', $weight, $matches);
        return !empty($matches) ? (float) $matches[0] : null;
    }

    protected function mapAvailability(string $availability): string
    {
        $availabilityMap = [
            'in stock' => 'in_stock',
            'out of stock' => 'out_of_stock',
            'preorder' => 'preorder',
            'backorder' => 'backorder'
        ];
        
        return $availabilityMap[strtolower($availability)] ?? 'in_stock';
    }

    protected function mapCondition(string $condition): string
    {
        $conditionMap = [
            'new' => 'new',
            'refurbished' => 'refurbished',
            'used' => 'used'
        ];
        
        return $conditionMap[strtolower($condition)] ?? 'new';
    }

    protected function mapCategory(string $categoryName, string $googleCategory = '', array $config = []): ?int
    {
        if (empty($categoryName) && empty($googleCategory)) {
            return $config['default_category_id'] ?? null;
        }
        
        // Try to find existing category by name
        $category = null;
        if (!empty($categoryName)) {
            // First try to find exact match for the full path
            $category = Category::where('name', $categoryName)->first();
            
            // If not found, try to find by path-like name
            if (!$category) {
                $category = Category::where('name', 'like', '%' . $categoryName . '%')->first();
            }
        }
        
        if (!$category && !empty($googleCategory)) {
            // Try to find by Google category
            $category = Category::where('google_category_id', $googleCategory)->first();
        }
        
        if (!$category) {
            // Create new category if allowed
            if ($config['create_categories'] ?? false) {
                // Use hierarchical category creation for paths with separators
                if (strpos($categoryName, ' > ') !== false) {
                    // Use the CategoryService to create hierarchical categories
                    $categoryService = new \App\Services\CategoryService();
                    $categoryId = $categoryService->createCategoryFromPath($categoryName);
                    if ($categoryId) {
                        $category = Category::find($categoryId);
                    }
                } else {
                    // Create single category
                    $category = Category::create([
                        'name' => $categoryName ?: 'Imported Category',
                        'slug' => \Str::slug($categoryName ?: 'imported-category'),
                        'is_active' => true,
                        'google_category_id' => $googleCategory ?: null
                    ]);
                }
            } else {
                return $config['default_category_id'] ?? null;
            }
        }
        
        return $category ? $category->id : ($config['default_category_id'] ?? null);
    }

    /**
     * Generate XML template for custom format
     */
    public function generateXmlTemplate(): string
    {
        $template = '<?xml version="1.0" encoding="UTF-8"?>
<products>
    <product>
        <name>Sample Product</name>
        <description>Product description here</description>
        <sku>SAMPLE-SKU-001</sku>
        <barcode>1234567890123</barcode>
        <brand>Sample Brand</brand>
        <category>Electronics</category>
        <price>99.99</price>
        <image>https://example.com/image.jpg</image>
    </product>
</products>';

        return $template;
    }

    /**
     * Generate Google Merchant Center XML template
     */
    public function generateGoogleMerchantTemplate(): string
    {
        $template = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>Your Store</title>
        <link>https://yourstore.com</link>
        <description>Product feed for Google Merchant Center</description>
        <item>
            <g:id>SKU001</g:id>
            <title>Sample Product</title>
            <description>Product description</description>
            <g:link>https://yourstore.com/product/sku001</g:link>
            <g:image_link>https://yourstore.com/images/sku001.jpg</g:image_link>
            <g:condition>new</g:condition>
            <g:availability>in stock</g:availability>
            <g:price>99.99 USD</g:price>
            <g:brand>Sample Brand</g:brand>
            <g:gtin>1234567890123</g:gtin>
            <g:mpn>MPN001</g:mpn>
            <g:product_type>Electronics > Computers</g:product_type>
            <g:google_product_category>Electronics</g:google_product_category>
        </item>
    </channel>
</rss>';

        return $template;
    }
}