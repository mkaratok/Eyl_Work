<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SellerDashboardController extends Controller
{
    /**
     * Main seller dashboard endpoint
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        Log::info('Seller dashboard accessed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')
        ]);

        // Basic dashboard data
        $stats = [
            'total_products' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'pending_orders' => 0,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Seller dashboard data retrieved successfully',
            'data' => $stats
        ]);
    }
}
