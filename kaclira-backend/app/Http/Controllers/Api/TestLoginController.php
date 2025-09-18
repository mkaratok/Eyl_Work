<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TestLoginController extends Controller
{
    /**
     * Simple test endpoint for seller login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testLogin(Request $request): JsonResponse
    {
        try {
            // Log the entire request for debugging
            Log::info('Test login request', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);

            // Return a simple success response
            return response()->json([
                'success' => true,
                'message' => 'Test endpoint reached successfully',
                'data' => [
                    'request_data' => $request->all()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Test login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error in test endpoint',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
