<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SellerController extends Controller
{
    /**
     * Seller dashboard for Kaçlıra.com sellers
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        // Log authentication information for debugging
        $user = Auth::guard('sanctum')->user();
        Log::info('Seller dashboard accessed', [
            'user_id' => $user ? $user->id : 'not authenticated',
            'auth_check' => Auth::guard('sanctum')->check() ? 'authenticated' : 'not authenticated',
            'token_exists' => request()->bearerToken() ? 'yes' : 'no'
        ]);
        
        // TODO: Implement seller dashboard logic
        $stats = [
            'total_products' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'pending_orders' => 0,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Seller dashboard data retrieved successfully',
            'data' => $stats
        ]);
    }

    /**
     * Get seller products
     *
     * @return JsonResponse
     */
    public function products(): JsonResponse
    {
        // TODO: Implement products retrieval for seller
        return response()->json([
            'success' => true,
            'message' => 'Seller products retrieved successfully',
            'data' => []
        ]);
    }

    /**
     * Add new product
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addProduct(Request $request): JsonResponse
    {
        // TODO: Implement product addition logic
        return response()->json([
            'success' => true,
            'message' => 'Product added successfully'
        ]);
    }

    /**
     * Update product
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateProduct(Request $request, int $id): JsonResponse
    {
        // TODO: Implement product update logic
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    }

    /**
     * Delete product
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteProduct(int $id): JsonResponse
    {
        // TODO: Implement product deletion logic
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}
