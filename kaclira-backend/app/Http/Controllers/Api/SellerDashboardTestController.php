<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SellerDashboardTestController extends Controller
{
    /**
     * Simple dashboard test endpoint
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Log all request details
        Log::info('Seller dashboard test controller accessed', [
            'headers' => $request->headers->all(),
            'token' => $request->bearerToken(),
            'user' => auth()->user()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seller dashboard test controller accessed successfully',
            'data' => [
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'roles' => auth()->user()->roles->pluck('name')
            ]
        ]);
    }
}
