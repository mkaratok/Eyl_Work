<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SanctumTestController extends Controller
{
    /**
     * Test endpoint to verify Sanctum authentication
     */
    public function test(Request $request)
    {
        // Log the request details
        Log::info('Sanctum test endpoint accessed', [
            'headers' => $request->headers->all(),
            'token_exists' => $request->bearerToken() ? 'yes' : 'no',
            'token' => $request->bearerToken()
        ]);

        // Check if user is authenticated
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Authentication failed',
            'data' => [
                'token_present' => $request->bearerToken() ? true : false
            ]
        ], 401);
    }
}
