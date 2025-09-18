<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Services\SellerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Exception;

class SubSellerController extends Controller
{
    protected SellerService $sellerService;

    public function __construct(SellerService $sellerService)
    {
        $this->sellerService = $sellerService;
    }

    /**
     * Get current seller's sub-sellers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller->canManageSubSellers()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to manage sub-sellers'
                ], 403);
            }

            $query = $seller->childSellers()
                ->withCount(['productPrices', 'orders']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'LIKE', "%{$search}%")
                      ->orWhere('contact_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $subSellers = $query->paginate($perPage);

            // Transform data
            $subSellers->getCollection()->transform(function ($subSeller) {
                return [
                    'id' => $subSeller->id,
                    'company_name' => $subSeller->company_name,
                    'contact_name' => $subSeller->contact_name,
                    'email' => $subSeller->email,
                    'phone' => $subSeller->phone,
                    'status' => $subSeller->status,
                    'commission_rate' => $subSeller->commission_rate,
                    'permissions' => $subSeller->permissions,
                    'logo_url' => $subSeller->logo_url,
                    'product_prices_count' => $subSeller->product_prices_count,
                    'orders_count' => $subSeller->orders_count,
                    'stats' => $subSeller->getDashboardStats(),
                    'created_at' => $subSeller->created_at,
                    'updated_at' => $subSeller->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $subSellers,
                'meta' => [
                    'can_create_more' => $seller->canCreateSubSeller(),
                    'max_sub_sellers' => $seller->getMaxSubSellersLimit(),
                    'current_count' => $seller->getSubSellersCount(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sub-sellers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sub-seller details
     */
    public function show(Request $request, Seller $subSeller): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            // Verify ownership
            if ($subSeller->parent_seller_id !== $seller->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-seller not found'
                ], 404);
            }

            $subSeller->load(['productPrices.product']);

            $subSellerData = [
                'id' => $subSeller->id,
                'company_name' => $subSeller->company_name,
                'contact_name' => $subSeller->contact_name,
                'email' => $subSeller->email,
                'phone' => $subSeller->phone,
                'address' => $subSeller->address,
                'tax_number' => $subSeller->tax_number,
                'status' => $subSeller->status,
                'commission_rate' => $subSeller->commission_rate,
                'permissions' => $subSeller->permissions,
                'settings' => $subSeller->settings,
                'logo_url' => $subSeller->logo_url,
                'description' => $subSeller->description,
                'website_url' => $subSeller->website_url,
                'social_links' => $subSeller->social_links,
                'stats' => $subSeller->getDashboardStats(),
                'analytics' => $this->sellerService->getSellerAnalytics($subSeller),
                'created_at' => $subSeller->created_at,
                'updated_at' => $subSeller->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $subSellerData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sub-seller details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new sub-seller
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller->canCreateSubSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot create more sub-sellers or lack permission'
                ], 403);
            }

            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'contact_name' => 'required|string|max:255',
                'email' => 'required|email|unique:sellers,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:50',
                'commission_rate' => 'nullable|numeric|min:0|max:100',
                'permissions' => 'nullable|array',
                'permissions.*' => ['string', Rule::in([
                    Seller::PERMISSION_MANAGE_PRODUCTS,
                    Seller::PERMISSION_MANAGE_PRICES,
                    Seller::PERMISSION_MANAGE_ORDERS,
                    Seller::PERMISSION_VIEW_ANALYTICS,
                    Seller::PERMISSION_EXPORT_DATA,
                ])],
                'description' => 'nullable|string|max:1000',
                'website_url' => 'nullable|url',
                'social_links' => 'nullable|array',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'create_user' => 'boolean',
                'password' => 'required_if:create_user,true|min:8',
                'send_welcome_email' => 'boolean',
            ]);

            // Validate permissions - seller can only delegate permissions they have
            if (isset($validated['permissions'])) {
                foreach ($validated['permissions'] as $permission) {
                    if (!$seller->hasPermission($permission)) {
                        return response()->json([
                            'success' => false,
                            'message' => "You do not have permission to delegate: {$permission}"
                        ], 403);
                    }
                }
            }

            $subSeller = $this->sellerService->createSubSeller($seller, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Sub-seller created successfully',
                'data' => $subSeller
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sub-seller',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update sub-seller
     */
    public function update(Request $request, Seller $subSeller): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            // Verify ownership
            if ($subSeller->parent_seller_id !== $seller->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-seller not found'
                ], 404);
            }

            $validated = $request->validate([
                'company_name' => 'sometimes|required|string|max:255',
                'contact_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:sellers,email,' . $subSeller->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:50',
                'commission_rate' => 'nullable|numeric|min:0|max:100',
                'description' => 'nullable|string|max:1000',
                'website_url' => 'nullable|url',
                'social_links' => 'nullable|array',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $this->sellerService->uploadLogo($request->file('logo'), $subSeller->id);
                $validated['logo_path'] = $logoPath;
            }

            $subSeller->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Sub-seller updated successfully',
                'data' => $subSeller->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sub-seller',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update sub-seller permissions
     */
    public function updatePermissions(Request $request, Seller $subSeller): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            // Verify ownership
            if ($subSeller->parent_seller_id !== $seller->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-seller not found'
                ], 404);
            }

            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => ['string', Rule::in([
                    Seller::PERMISSION_MANAGE_PRODUCTS,
                    Seller::PERMISSION_MANAGE_PRICES,
                    Seller::PERMISSION_MANAGE_ORDERS,
                    Seller::PERMISSION_VIEW_ANALYTICS,
                    Seller::PERMISSION_EXPORT_DATA,
                ])],
            ]);

            // Validate permissions - seller can only delegate permissions they have
            foreach ($validated['permissions'] as $permission) {
                if (!$seller->hasPermission($permission)) {
                    return response()->json([
                        'success' => false,
                        'message' => "You do not have permission to delegate: {$permission}"
                    ], 403);
                }
            }

            $this->sellerService->updateSubSellerPermissions(
                $seller,
                $subSeller,
                $validated['permissions']
            );

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully',
                'data' => [
                    'permissions' => $subSeller->fresh()->permissions
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Activate/Deactivate sub-seller
     */
    public function toggleStatus(Request $request, Seller $subSeller): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            // Verify ownership
            if ($subSeller->parent_seller_id !== $seller->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-seller not found'
                ], 404);
            }

            $newStatus = $subSeller->status === Seller::STATUS_ACTIVE 
                ? Seller::STATUS_SUSPENDED 
                : Seller::STATUS_ACTIVE;

            if ($newStatus === Seller::STATUS_SUSPENDED) {
                $this->sellerService->suspendSeller($subSeller, 'Suspended by parent seller');
            } else {
                $this->sellerService->reactivateSeller($subSeller);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sub-seller status updated successfully',
                'data' => [
                    'status' => $subSeller->fresh()->status
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete sub-seller
     */
    public function destroy(Request $request, Seller $subSeller): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            // Verify ownership
            if ($subSeller->parent_seller_id !== $seller->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-seller not found'
                ], 404);
            }

            $this->sellerService->deleteSeller($subSeller);

            return response()->json([
                'success' => true,
                'message' => 'Sub-seller deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sub-seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available permissions for delegation
     */
    public function availablePermissions(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            $allPermissions = [
                Seller::PERMISSION_MANAGE_PRODUCTS => 'Manage Products',
                Seller::PERMISSION_MANAGE_PRICES => 'Manage Prices',
                Seller::PERMISSION_MANAGE_ORDERS => 'Manage Orders',
                Seller::PERMISSION_VIEW_ANALYTICS => 'View Analytics',
                Seller::PERMISSION_EXPORT_DATA => 'Export Data',
            ];

            $availablePermissions = [];
            foreach ($allPermissions as $permission => $label) {
                if ($seller->hasPermission($permission)) {
                    $availablePermissions[$permission] = $label;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $availablePermissions
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update sub-seller permissions
     */
    public function bulkUpdatePermissions(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            $validated = $request->validate([
                'sub_seller_ids' => 'required|array|min:1',
                'sub_seller_ids.*' => 'exists:sellers,id',
                'permissions' => 'required|array',
                'permissions.*' => ['string', Rule::in([
                    Seller::PERMISSION_MANAGE_PRODUCTS,
                    Seller::PERMISSION_MANAGE_PRICES,
                    Seller::PERMISSION_MANAGE_ORDERS,
                    Seller::PERMISSION_VIEW_ANALYTICS,
                    Seller::PERMISSION_EXPORT_DATA,
                ])],
            ]);

            // Validate all sub-sellers belong to current seller
            $subSellers = Seller::whereIn('id', $validated['sub_seller_ids'])
                ->where('parent_seller_id', $seller->id)
                ->get();

            if ($subSellers->count() !== count($validated['sub_seller_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some sub-sellers not found or do not belong to you'
                ], 404);
            }

            // Validate permissions
            foreach ($validated['permissions'] as $permission) {
                if (!$seller->hasPermission($permission)) {
                    return response()->json([
                        'success' => false,
                        'message' => "You do not have permission to delegate: {$permission}"
                    ], 403);
                }
            }

            $results = [];
            foreach ($subSellers as $subSeller) {
                try {
                    $this->sellerService->updateSubSellerPermissions(
                        $seller,
                        $subSeller,
                        $validated['permissions']
                    );

                    $results[] = [
                        'sub_seller_id' => $subSeller->id,
                        'success' => true,
                        'message' => 'Permissions updated successfully'
                    ];

                } catch (Exception $e) {
                    $results[] = [
                        'sub_seller_id' => $subSeller->id,
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            return response()->json([
                'success' => true,
                'message' => "Bulk permission update completed: {$successCount}/{$totalCount} successful",
                'data' => $results
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk permission update',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get sub-seller analytics summary
     */
    public function analyticsSummary(Request $request): JsonResponse
    {
        try {
            $seller = $request->user()->seller;

            if (!$seller->canManageSubSellers()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view sub-seller analytics'
                ], 403);
            }

            $analytics = $this->sellerService->getSellerAnalytics($seller, [
                'include_sub_sellers' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'consolidated_stats' => $analytics['consolidated_stats'] ?? null,
                    'sub_sellers_stats' => $analytics['sub_sellers_stats'] ?? [],
                    'performance_comparison' => $this->getSubSellerPerformanceComparison($seller),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sub-seller performance comparison
     */
    protected function getSubSellerPerformanceComparison(Seller $seller): array
    {
        $subSellers = $seller->childSellers()->active()->get();
        $comparison = [];

        foreach ($subSellers as $subSeller) {
            $stats = $subSeller->getDashboardStats();
            $comparison[] = [
                'id' => $subSeller->id,
                'company_name' => $subSeller->company_name,
                'total_revenue' => $stats['total_revenue'],
                'monthly_revenue' => $stats['monthly_revenue'],
                'total_orders' => $stats['total_orders'],
                'monthly_orders' => $stats['monthly_orders'],
                'avg_order_value' => $stats['avg_order_value'],
                'total_products' => $stats['total_products'],
                'active_products' => $stats['active_products'],
            ];
        }

        // Sort by monthly revenue
        usort($comparison, function ($a, $b) {
            return $b['monthly_revenue'] <=> $a['monthly_revenue'];
        });

        return $comparison;
    }
}
