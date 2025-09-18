<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class TokenTestController extends Controller
{
    /**
     * Simple test endpoint to verify token authentication
     */
    public function test(Request $request)
    {
        // Log all request details
        Log::info('Token test endpoint accessed', [
            'headers' => $request->headers->all(),
            'token' => $request->bearerToken()
        ]);

        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No token provided',
                'data' => null
            ], 401);
        }
        
        try {
            // Manually check the token
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'data' => null
                ], 401);
            }
            
            $user = $accessToken->tokenable;
            
            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Token validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error validating token: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
