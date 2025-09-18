<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\SellerAuthController;
use App\Http\Controllers\Api\SellerAuthControllerSimple;
use App\Http\Controllers\Api\SellerLoginTestController;
use App\Http\Controllers\Api\TestLoginController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\FilterController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SellerController as AdminSellerController;
use App\Http\Controllers\Seller\SellerController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;

/*
|--------------------------------------------------------------------------
| API Routes for Kaçlıra.com Price Comparison System
|--------------------------------------------------------------------------
|
| Multi-Auth API Routes Structure:
| - /api/v1/auth/* - General authentication
| - /api/v1/admin/* - Admin routes
| - /api/v1/seller/* - Seller routes  
| - /api/v1/user/* - User routes
| - /api/v1/public/* - Public routes
|
*/

// =============================================================================
// DIRECT SELLER ROUTES (without v1 prefix for compatibility)
// =============================================================================
Route::prefix('seller')->middleware(['rate.limit:seller'])->group(function () {
    // Seller authentication (no auth required)
    Route::post('/login', [SellerAuthController::class, 'login']);
    Route::post('/register', [SellerAuthController::class, 'register']);
    Route::post('/login-test', [SellerLoginTestController::class, 'login']);
    Route::get('/dashboard-test', [SellerController::class, 'dashboard']);
    Route::get('/sanctum-test', [\App\Http\Controllers\Api\SanctumTestController::class, 'test']);
    Route::get('/direct-test', [\App\Http\Controllers\Api\DirectSanctumTestController::class, 'test']);
    
    // Protected seller routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [SellerAuthController::class, 'logout']);
        Route::post('/refresh', [SellerAuthController::class, 'refresh']);
        Route::get('/me', [SellerAuthController::class, 'me']);
        Route::put('/profile', [SellerAuthController::class, 'updateProfile']);
        Route::post('/change-password', [SellerAuthController::class, 'changePassword']);
        
        // Seller dashboard and management
        Route::get('/dashboard', [\App\Http\Controllers\Api\SellerDashboardController::class, 'index']);
        Route::get('/dashboard-direct', [\App\Http\Controllers\Api\SellerDashboardController::class, 'index']);
        Route::get('/products', [SellerProductController::class, 'index']);
        Route::get('/products/stats', [SellerProductController::class, 'stats']);
        Route::get('/products/{id}', [SellerProductController::class, 'show']);
        Route::post('/products', [SellerProductController::class, 'store']);
        Route::put('/products/{id}', [SellerProductController::class, 'update']);
        Route::delete('/products/{id}', [SellerProductController::class, 'destroy']);
        Route::post('/products/import-xml', [SellerProductController::class, 'importXml']);
        Route::get('/products/xml-template', [SellerProductController::class, 'downloadXmlTemplate']);
        
        // Price Management
        Route::get('/prices', [App\Http\Controllers\Seller\PriceController::class, 'index']);
        Route::post('/products/{id}/price', [App\Http\Controllers\Seller\PriceController::class, 'updatePrice']);
        Route::post('/bulk-price-update', [App\Http\Controllers\Seller\PriceController::class, 'bulkUpdatePrices']);
        Route::get('/price-performance', [App\Http\Controllers\Seller\PriceController::class, 'getPerformance']);
        Route::get('/products/{id}/price-history', [App\Http\Controllers\Seller\PriceController::class, 'getPriceHistory']);
        Route::post('/products/{id}/toggle-active', [App\Http\Controllers\Seller\PriceController::class, 'toggleActive']);
        Route::get('/products/{id}/price-comparison', [App\Http\Controllers\Seller\PriceController::class, 'getComparison']);
        Route::get('/prices/export', [App\Http\Controllers\Seller\PriceController::class, 'exportPrices']);
        
        // Analytics
        Route::get('/analytics', function () {
            return response()->json(['message' => 'Seller analytics - to be implemented']);
        });
        Route::get('/analytics/products', function () {
            return response()->json(['message' => 'Seller product analytics - to be implemented']);
        });
        Route::get('/analytics/sales', function () {
            return response()->json(['message' => 'Seller sales analytics - to be implemented']);
        });
        
        // XML Import routes
        Route::prefix('import')->group(function () {
            Route::post('/analyze', [\App\Http\Controllers\Seller\ImportController::class, 'analyze']);
            Route::post('/process', [\App\Http\Controllers\Seller\ImportController::class, 'process']);
            Route::get('/status/{jobId}', [\App\Http\Controllers\Seller\ImportController::class, 'status']);
        });
    });
});

// =============================================================================
// API V1 ROUTES
// =============================================================================
Route::prefix('v1')->group(function () {
    
    // PUBLIC ROUTES (No Authentication Required)
    Route::prefix('public')->middleware(['rate.limit:public'])->group(function () {
        // Public product endpoints
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/search', [ProductController::class, 'search']);
        Route::get('/products/suggestions', [ProductController::class, 'suggestions']);
        Route::get('/products/featured', [ProductController::class, 'featured']);
        Route::get('/products/brands', [ProductController::class, 'brands']);
        Route::get('/products/stats', [ProductController::class, 'stats']);
        Route::get('/products/category/{categoryId}', [ProductController::class, 'byCategory']);
        Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/products/{id}/related', [ProductController::class, 'related']);
        
        // Category endpoints
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/flat', [CategoryController::class, 'flat']);
        Route::get('/categories/featured', [CategoryController::class, 'featured']);
        Route::get('/categories/search', [CategoryController::class, 'search']);
        Route::get('/categories/level/{level}', [CategoryController::class, 'byLevel']);
        Route::get('/categories/stats', [CategoryController::class, 'stats']);
        Route::get('/categories/{identifier}', [CategoryController::class, 'show']);
        Route::post('/categories/suggest', [CategoryController::class, 'suggest']);
        
        // Search endpoints
        Route::get('/search', [SearchController::class, 'search']);
        Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
        Route::get('/search/popular', [SearchController::class, 'popular']);
        Route::get('/search/quick', [SearchController::class, 'quickSearch']);
        
        // Filter endpoints
        Route::get('/filters/options', [FilterController::class, 'options']);
        Route::get('/filters/categories', [FilterController::class, 'categories']);
        Route::get('/filters/brands', [FilterController::class, 'brands']);
        Route::get('/filters/sellers', [FilterController::class, 'sellers']);
        Route::get('/filters/price-range', [FilterController::class, 'priceRange']);
        Route::get('/filters/sort-options', [FilterController::class, 'sortOptions']);
        Route::post('/filters/dynamic', [FilterController::class, 'dynamicFilters']);
    });

    // =============================================================================
    // GENERAL AUTHENTICATION ROUTES
    // =============================================================================
    Route::prefix('auth')->middleware(['rate.limit:auth'])->group(function () {
        // User registration and login
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        
        // Protected auth routes
        Route::middleware(['auth:api'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'profile']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            
            // API Key Management
            Route::apiResource('api-keys', \App\Http\Controllers\Api\ApiKeyController::class);
            Route::post('/api-keys/{id}/regenerate', [\App\Http\Controllers\Api\ApiKeyController::class, 'regenerate']);
            Route::post('/api-keys/{id}/revoke', [\App\Http\Controllers\Api\ApiKeyController::class, 'revoke']);
            Route::get('/api-keys/{id}/usage', [\App\Http\Controllers\Api\ApiKeyController::class, 'usage']);
        });
    });

    // =============================================================================
    // ADMIN AUTHENTICATION ROUTES (No Authentication Required for login)
    // =============================================================================
    Route::prefix('admin')->middleware(['rate.limit:admin'])->group(function () {
        // Admin authentication - NO MIDDLEWARE REQUIRED FOR LOGIN
        Route::post('/login', [AdminAuthController::class, 'login']);
        
        // Protected admin routes - THESE REQUIRE AUTHENTICATION
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout']);
            Route::post('/refresh', [AdminAuthController::class, 'refresh']);
            Route::get('/me', [AdminAuthController::class, 'me']);
            Route::put('/profile', [AdminAuthController::class, 'updateProfile']);
            Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
            
            // Dashboard
            Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
            Route::get('/dashboard/realtime', [AdminDashboardController::class, 'realtime']);
            Route::get('/dashboard/health', [AdminDashboardController::class, 'health']);
            Route::get('/dashboard/activities', [AdminDashboardController::class, 'activities']);
            Route::get('/dashboard/quick-actions', [AdminDashboardController::class, 'quickActions']);
            Route::post('/dashboard/export', [AdminDashboardController::class, 'export']);
            
            // Category Management
            Route::apiResource('categories', AdminCategoryController::class);
            Route::post('/categories/sync', [AdminCategoryController::class, 'sync']);
            
            // Order Management
            Route::apiResource('orders', \App\Http\Controllers\Admin\OrderController::class);
            Route::patch('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus']);
            
            // User Management
            Route::apiResource('users', AdminUserController::class);
            Route::post('/users/{id}/toggle-ban', [AdminUserController::class, 'toggleBan']);
            Route::post('/users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
            Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction']);
            
            // Seller Management
            Route::apiResource('sellers', AdminSellerController::class);
            Route::post('/sellers/{id}/approve', [AdminSellerController::class, 'approve']);
            Route::post('/sellers/{id}/reject', [AdminSellerController::class, 'reject']);
            Route::post('/sellers/{id}/toggle-verification', [AdminSellerController::class, 'toggleVerification']);
            Route::post('/sellers/bulk-action', [AdminSellerController::class, 'bulkAction']);
            
            // Product Management
            Route::get('/products', [AdminProductController::class, 'index']);
            Route::post('/products', [AdminProductController::class, 'store']);
            Route::get('/products/pending', [AdminProductController::class, 'pending']);
            Route::get('/products/stats', [AdminProductController::class, 'stats']);
            Route::get('/products/{id}', [AdminProductController::class, 'show']);
            Route::post('/products/{id}/approve', [AdminProductController::class, 'approve']);
            Route::post('/products/{id}/reject', [AdminProductController::class, 'reject']);
            Route::post('/products/{id}/toggle-featured', [AdminProductController::class, 'toggleFeatured']);
            Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
            Route::post('/products/bulk-approve', [AdminProductController::class, 'bulkApprove']);
            Route::post('/products/bulk-reject', [AdminProductController::class, 'bulkReject']);
            Route::post('/products/bulk-delete', [AdminProductController::class, 'bulkDelete']);
            Route::post('/products/check-duplicates', [AdminProductController::class, 'checkDuplicates']);
            Route::post('/products/validate-barcode', [AdminProductController::class, 'validateBarcode']);
            Route::post('/products/import', [AdminProductController::class, 'import']);
            
            // Add debugging before the import-xml route
            Route::match(['get', 'post'], '/products/import-xml-debug', function() {
                \Log::info('Debug route hit: /products/import-xml-debug');
                return response()->json(['message' => 'Debug route working']);
            });
            
            // Update to handle both POST and HEAD requests
            Route::match(['post', 'head'], '/products/import-xml', [AdminProductController::class, 'importXml']);
            Route::get('/products/xml-template', [AdminProductController::class, 'downloadXmlTemplate']);
            Route::post('/products/import/preview', [AdminProductController::class, 'importPreview']);
            Route::post('/products/import/validate', [AdminProductController::class, 'validateImportFile']);
            Route::get('/products/import/template', [AdminProductController::class, 'importTemplate']);
            Route::get('/products/export', [AdminProductController::class, 'export']);
            
            // Reports & Analytics
            Route::get('/reports/users', [AdminReportController::class, 'usersReport']);
            Route::get('/reports/products', [AdminReportController::class, 'productsReport']);
            Route::get('/reports/sellers', [AdminReportController::class, 'sellersReport']);
            Route::get('/reports/activity', [AdminReportController::class, 'activityReport']);
            Route::get('/reports/system-stats', [AdminReportController::class, 'systemStats']);
            Route::get('/reports/performance', [AdminReportController::class, 'performanceMetrics']);
            Route::post('/reports/export', [AdminReportController::class, 'exportComprehensive']);
            
            // Notifications
            Route::get('/notifications', [AdminNotificationController::class, 'index']);
            Route::get('/notifications/stats', [AdminNotificationController::class, 'getStats']);
            Route::get('/notifications/user-preferences-summary', [AdminNotificationController::class, 'getUserPreferencesSummary']);
            Route::post('/notifications/campaign', [AdminNotificationController::class, 'sendCampaign']);
            Route::post('/notifications/test', [AdminNotificationController::class, 'testNotification']);
        });
    });

    // =============================================================================
    // USER ROUTES (Authenticated Users)
    // =============================================================================
    Route::prefix('user')->middleware(['auth:api', 'rate.limit:user'])->group(function () {
        // User favorites
        Route::get('/favorites', function () {
            return response()->json(['message' => 'User favorites - to be implemented']);
        });
        Route::post('/favorites', function () {
            return response()->json(['message' => 'Add to favorites - to be implemented']);
        });
        Route::delete('/favorites/{id}', function ($id) {
            return response()->json(['message' => "Remove from favorites {$id} - to be implemented"]);
        });
        
        // Price history and comparison
        Route::get('/products/{id}/price-history', [App\Http\Controllers\Api\PriceController::class, 'getPriceHistory']);
        Route::get('/products/{id}/price-comparison', [App\Http\Controllers\Api\PriceController::class, 'getPriceComparison']);
        Route::get('/products/{id}/price-chart', [App\Http\Controllers\Api\PriceController::class, 'getPriceChartData']);
        Route::get('/products/{id}/price-statistics', [App\Http\Controllers\Api\PriceController::class, 'getPriceStatistics']);
        Route::get('/products/{id}/price-alerts', [App\Http\Controllers\Api\PriceController::class, 'getPriceAlerts']);
        Route::get('/trending-prices', [App\Http\Controllers\Api\PriceController::class, 'getTrendingPrices']);
        
        // Price alerts
        Route::get('/price-alerts', function () {
            return response()->json(['message' => 'User price alerts - to be implemented']);
        });
        Route::post('/price-alerts', function () {
            return response()->json(['message' => 'Create price alert - to be implemented']);
        });
        Route::put('/price-alerts/{id}', function ($id) {
            return response()->json(['message' => "Update price alert {$id} - to be implemented"]);
        });
        Route::delete('/price-alerts/{id}', function ($id) {
            return response()->json(['message' => "Delete price alert {$id} - to be implemented"]);
        });
        
        // Comparison history
        Route::get('/comparisons', function () {
            return response()->json(['message' => 'User comparison history - to be implemented']);
        });
        Route::post('/comparisons', function () {
            return response()->json(['message' => 'Save comparison - to be implemented']);
        });
        
        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences']);
        Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
        Route::post('/notifications/push-token', [NotificationController::class, 'registerPushToken']);
        Route::delete('/notifications/push-token', [NotificationController::class, 'unregisterPushToken']);
        Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
    });
});

// =============================================================================
// API V2 ROUTES (Enhanced Features)
// =============================================================================
Route::prefix('v2')->group(function () {
    
    // PUBLIC ROUTES with V2 enhancements
    Route::prefix('public')->middleware(['rate.limit:public'])->group(function () {
        Route::get('/products', [\App\Http\Controllers\Api\V2\ProductController::class, 'index']);
        Route::get('/products/{slug}', [\App\Http\Controllers\Api\V2\ProductController::class, 'show']);
        Route::get('/products/{id}/price-history', [\App\Http\Controllers\Api\V2\ProductController::class, 'priceHistory']);
    });
    
    // AUTH ROUTES
    Route::prefix('auth')->middleware(['rate.limit:auth'])->group(function () {
        Route::middleware(['auth:api'])->group(function () {
            // API Key Management (V2 enhanced)
            Route::apiResource('api-keys', \App\Http\Controllers\Api\ApiKeyController::class);
            Route::post('/api-keys/{id}/regenerate', [\App\Http\Controllers\Api\ApiKeyController::class, 'regenerate']);
            Route::post('/api-keys/{id}/revoke', [\App\Http\Controllers\Api\ApiKeyController::class, 'revoke']);
            Route::get('/api-keys/{id}/usage', [\App\Http\Controllers\Api\ApiKeyController::class, 'usage']);
        });
    });
});

// =============================================================================
// HEALTH CHECK & API INFO
// =============================================================================
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Kaçlıra.com API is running',
        'timestamp' => now(),
        'version' => '2.0.0',
        'supported_versions' => ['v1', 'v2'],
        'latest_version' => 'v2'
    ]);
});

Route::get('/api-info', function () {
    return response()->json([
        'api_name' => 'Kaçlıra API',
        'description' => 'Price comparison platform API',
        'version' => '2.0.0',
        'supported_versions' => ['v1', 'v2'],
        'documentation' => url('/api/documentation'),
        'postman_collection' => url('/api/postman/collection'),
        'rate_limits' => [
            'guest' => '100 requests/hour',
            'user' => '500 requests/hour', 
            'seller' => '1000 requests/hour',
            'admin' => 'unlimited'
        ],
        'security_features' => [
            'sanctum_authentication',
            'api_key_authentication',
            'rate_limiting',
            'cors_protection',
            'xss_protection',
            'sql_injection_protection',
            'request_validation'
        ]
    ]);
});

// =============================================================================
// API DOCUMENTATION ROUTES
// =============================================================================
Route::prefix('documentation')->group(function () {
    Route::get('/swagger', [\App\Http\Controllers\Api\DocumentationController::class, 'swagger']);
    Route::get('/postman', [\App\Http\Controllers\Api\DocumentationController::class, 'postman']);
    Route::get('/endpoints', [\App\Http\Controllers\Api\DocumentationController::class, 'endpoints']);
});

// =============================================================================
// TEST ROUTES
// =============================================================================
Route::prefix('test')->group(function () {
    Route::get('/token', [\App\Http\Controllers\Api\TokenTestController::class, 'test']);
    Route::middleware('sanctum.seller')->get('/dashboard', [\App\Http\Controllers\Api\DashboardTestController::class, 'index']);
    Route::middleware('sanctum.seller')->get('/seller-dashboard', [\App\Http\Controllers\Api\SellerDashboardTestController::class, 'dashboard']);
    
    // Auth debug endpoint
    Route::get('/auth-status', function() {
        $adminUser = Auth::guard('admin')->user();
        $webUser = Auth::guard('web')->user();
        $defaultUser = Auth::user();
        
        return response()->json([
            'admin_guard' => $adminUser ? [
                'id' => $adminUser->id,
                'email' => $adminUser->email,
                'roles' => $adminUser->getRoleNames(),
                'is_active' => $adminUser->is_active
            ] : null,
            'web_guard' => $webUser ? [
                'id' => $webUser->id,
                'email' => $webUser->email,
                'roles' => $webUser->getRoleNames(),
                'is_active' => $webUser->is_active
            ] : null,
            'default_guard' => $defaultUser ? [
                'id' => $defaultUser->id,
                'email' => $defaultUser->email,
                'roles' => $defaultUser->getRoleNames(),
                'is_active' => $defaultUser->is_active
            ] : null
        ]);
    });
});

Route::post('/test/seller/login', [TestLoginController::class, 'testLogin']);
Route::post('/seller/login-test', [SellerLoginTestController::class, 'login']);