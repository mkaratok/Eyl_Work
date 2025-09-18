<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DirectSanctumTestController extends Controller
{
    /**
     * Test endpoint to verify Sanctum authentication directly
     */
    public function test(Request $request)
    {
        // Log detailed request information
        Log::info('Direct Sanctum test endpoint accessed', [
            'headers' => $request->headers->all(),
            'token_exists' => $request->bearerToken() ? 'yes' : 'no',
            'token' => $request->bearerToken()
        ]);

        // Try to authenticate with the token
        $user = null;
        $token = $request->bearerToken();
        
        if ($token) {
            // Find the token in the personal_access_tokens table
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if ($accessToken) {
                $user = $accessToken->tokenable;
                Log::info('Token found in database', [
                    'token_id' => $accessToken->id,
                    'user_id' => $user ? $user->id : 'null',
                    'user_email' => $user ? $user->email : 'null'
                ]);
            } else {
                Log::error('Token not found in database');
            }
        }

        // Return response based on authentication result
        if ($user) {
            return response()->json([
                'success' => true,
                'message' => 'Direct authentication successful',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : []
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Direct authentication failed',
            'data' => [
                'token_present' => $token ? true : false
            ]
        ], 401);
    }
}
