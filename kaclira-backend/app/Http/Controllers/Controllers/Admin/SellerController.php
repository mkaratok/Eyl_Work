<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Services\SellerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Exception;

class SellerController extends Controller
{
    protected SellerService $sellerService;

    public function __construct(SellerService $sellerService)
    {
        $this->sellerService = $sellerService;
    }

    /**
     * Get all sellers with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Seller::with(['parentSeller', 'childSellers'])
                ->withCount(['productPrices', 'childSellers', 'orders']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('subscription_type')) {
                $query->where('subscription_type', $request->subscription_type);
            }

            if ($request->filled('parent_only')) {
                $query->parents();
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
            $perPage = min($request->get('per_page', 15), 100);
            $sellers = $query->paginate($perPage);

            // Add computed fields
            $sellers->getCollection()->transform(function ($seller) {
                return [
                    'id' => $seller->id,
                    'company_name' => $seller->company_name,
                    'contact_name' => $seller->contact_name,
                    'email' => $seller->email,
                    'phone' => $seller->phone,
                    'status' => $seller->status,
                    'subscription_type' => $seller->subscription_type,
                    'subscription_expires_at' => $seller->subscription_expires_at,
                    'commission_rate' => $seller->commission_rate,
                    'is_parent' => $seller->is_parent,
                    'parent_seller' => $seller->parentSeller ? [
                        'id' => $seller->parentSeller->id,
                        'company_name' => $seller->parentSeller->company_name,
                    ] : null,
                    'product_prices_count' => $seller->product_prices_count,
                    'child_sellers_count' => $seller->child_sellers_count,
                    'orders_count' => $seller->orders_count,
                    'logo_url' => $seller->logo_url,
                    'created_at' => $seller->created_at,
                    'updated_at' => $seller->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $sellers,
                'meta' => [
                    'total_sellers' => Seller::count(),
                    'active_sellers' => Seller::active()->count(),
                    'pending_sellers' => Seller::pending()->count(),
                    'parent_sellers' => Seller::parents()->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sellers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seller details
     */
    public function show(Seller $seller): JsonResponse
    {
        try {
            $seller->load(['parentSeller', 'childSellers', 'productPrices.product']);
            
            $sellerData = [
                'id' => $seller->id,
                'company_name' => $seller->company_name,
                'contact_name' => $seller->contact_name,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'address' => $seller->address,
                'tax_number' => $seller->tax_number,
                'status' => $seller->status,
                'subscription_type' => $seller->subscription_type,
                'subscription_expires_at' => $seller->subscription_expires_at,
                'commission_rate' => $seller->commission_rate,
                'permissions' => $seller->permissions,
                'settings' => $seller->settings,
                'logo_url' => $seller->logo_url,
                'description' => $seller->description,
                'website_url' => $seller->website_url,
                'social_links' => $seller->social_links,
                'is_parent' => $seller->is_parent,
                'parent_seller' => $seller->parentSeller,
                'child_sellers' => $seller->childSellers,
                'stats' => $seller->getDashboardStats(),
                'analytics' => $this->sellerService->getSellerAnalytics($seller, [
                    'include_sub_sellers' => true
                ]),
                'created_at' => $seller->created_at,
                'updated_at' => $seller->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $sellerData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch seller details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new seller
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'contact_name' => 'required|string|max:255',
                'email' => 'required|email|unique:sellers,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:50',
                'commission_rate' => 'nullable|numeric|min:0|max:100',
                'subscription_type' => ['required', Rule::in([
                    Seller::SUBSCRIPTION_BASIC,
                    Seller::SUBSCRIPTION_PREMIUM,
                    Seller::SUBSCRIPTION_ENTERPRISE
                ])],
                'subscription_expires_at' => 'required|date|after:today',
                'description' => 'nullable|string|max:1000',
                'website_url' => 'nullable|url',
                'social_links' => 'nullable|array',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'create_user' => 'boolean',
                'password' => 'required_if:create_user,true|min:8',
                'send_welcome_email' => 'boolean',
            ]);

            $validated['status'] = Seller::STATUS_ACTIVE; // Admin can create active sellers

            $seller = $this->sellerService->createSubSeller(
                new Seller(), // Dummy parent for admin creation
                $validated
            );

            // For admin-created sellers, set as parent (no parent_seller_id)
            $seller->update(['parent_seller_id' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Seller created successfully',
                'data' => $seller
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create seller',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update seller
     */
    public function update(Request $request, Seller $seller): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_name' => 'sometimes|required|string|max:255',
                'contact_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:sellers,email,' . $seller->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:50',
                'commission_rate' => 'nullable|numeric|min:0|max:100',
                'subscription_type' => ['sometimes', Rule::in([
                    Seller::SUBSCRIPTION_BASIC,
                    Seller::SUBSCRIPTION_PREMIUM,
                    Seller::SUBSCRIPTION_ENTERPRISE
                ])],
                'subscription_expires_at' => 'sometimes|date',
                'permissions' => 'nullable|array',
                'settings' => 'nullable|array',
                'description' => 'nullable|string|max:1000',
                'website_url' => 'nullable|url',
                'social_links' => 'nullable|array',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $this->sellerService->uploadLogo($request->file('logo'), $seller->id);
                $validated['logo_path'] = $logoPath;
            }

            $seller->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Seller updated successfully',
                'data' => $seller->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update seller',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Approve seller
     */
    public function approve(Seller $seller): JsonResponse
    {
        try {
            if ($seller->status !== Seller::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending sellers can be approved'
                ], 422);
            }

            $this->sellerService->approveSeller($seller);

            return response()->json([
                'success' => true,
                'message' => 'Seller approved successfully',
                'data' => $seller->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject seller
     */
    public function reject(Request $request, Seller $seller): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:1000'
            ]);

            if ($seller->status !== Seller::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending sellers can be rejected'
                ], 422);
            }

            $this->sellerService->rejectSeller($seller, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Seller rejected successfully',
                'data' => $seller->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspend seller
     */
    public function suspend(Request $request, Seller $seller): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:1000'
            ]);

            if ($seller->status === Seller::STATUS_SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seller is already suspended'
                ], 422);
            }

            $this->sellerService->suspendSeller($seller, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Seller suspended successfully',
                'data' => $seller->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate seller
     */
    public function reactivate(Seller $seller): JsonResponse
    {
        try {
            if ($seller->status !== Seller::STATUS_SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only suspended sellers can be reactivated'
                ], 422);
            }

            $this->sellerService->reactivateSeller($seller);

            return response()->json([
                'success' => true,
                'message' => 'Seller reactivated successfully',
                'data' => $seller->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete seller
     */
    public function destroy(Seller $seller): JsonResponse
    {
        try {
            $this->sellerService->deleteSeller($seller);

            return response()->json([
                'success' => true,
                'message' => 'Seller deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seller statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_sellers' => Seller::count(),
                'active_sellers' => Seller::active()->count(),
                'pending_sellers' => Seller::pending()->count(),
                'suspended_sellers' => Seller::where('status', Seller::STATUS_SUSPENDED)->count(),
                'rejected_sellers' => Seller::where('status', Seller::STATUS_REJECTED)->count(),
                'parent_sellers' => Seller::parents()->count(),
                'sub_sellers' => Seller::children()->count(),
                'subscription_stats' => [
                    'basic' => Seller::withSubscription(Seller::SUBSCRIPTION_BASIC)->count(),
                    'premium' => Seller::withSubscription(Seller::SUBSCRIPTION_PREMIUM)->count(),
                    'enterprise' => Seller::withSubscription(Seller::SUBSCRIPTION_ENTERPRISE)->count(),
                ],
                'recent_registrations' => Seller::where('created_at', '>=', now()->subDays(30))->count(),
                'expiring_subscriptions' => Seller::where('subscription_expires_at', '<=', now()->addDays(30))->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions on sellers
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:approve,reject,suspend,reactivate,delete',
                'seller_ids' => 'required|array|min:1',
                'seller_ids.*' => 'exists:sellers,id',
                'reason' => 'required_if:action,reject,suspend|string|max:1000'
            ]);

            $sellers = Seller::whereIn('id', $validated['seller_ids'])->get();
            $results = [];

            foreach ($sellers as $seller) {
                try {
                    switch ($validated['action']) {
                        case 'approve':
                            $this->sellerService->approveSeller($seller);
                            break;
                        case 'reject':
                            $this->sellerService->rejectSeller($seller, $validated['reason']);
                            break;
                        case 'suspend':
                            $this->sellerService->suspendSeller($seller, $validated['reason']);
                            break;
                        case 'reactivate':
                            $this->sellerService->reactivateSeller($seller);
                            break;
                        case 'delete':
                            $this->sellerService->deleteSeller($seller);
                            break;
                    }
                    
                    $results[] = [
                        'seller_id' => $seller->id,
                        'success' => true,
                        'message' => "Action '{$validated['action']}' completed successfully"
                    ];

                } catch (Exception $e) {
                    $results[] = [
                        'seller_id' => $seller->id,
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            return response()->json([
                'success' => true,
                'message' => "Bulk action completed: {$successCount}/{$totalCount} successful",
                'data' => $results
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update seller subscription
     */
    public function updateSubscription(Request $request, Seller $seller): JsonResponse
    {
        try {
            $validated = $request->validate([
                'subscription_type' => ['required', Rule::in([
                    Seller::SUBSCRIPTION_BASIC,
                    Seller::SUBSCRIPTION_PREMIUM,
                    Seller::SUBSCRIPTION_ENTERPRISE
                ])],
                'expires_at' => 'required|date|after:today'
            ]);

            $this->sellerService->updateSubscription(
                $seller,
                $validated['subscription_type'],
                \Carbon\Carbon::parse($validated['expires_at'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully',
                'data' => $seller->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seller hierarchy
     */
    public function hierarchy(Seller $seller): JsonResponse
    {
        try {
            $hierarchy = $this->sellerService->getSellerHierarchy($seller);

            return response()->json([
                'success' => true,
                'data' => $hierarchy
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch seller hierarchy',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
