<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SellerLoginTestController extends Controller
{
    /**
     * Minimal seller login test
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Log the entire request for debugging
            Log::info('Seller login test request', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);

            // Basic validation
            if (!$request->has('email') || !$request->has('password')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email and password are required',
                ], 422);
            }

            // Find user
            $user = User::where('email', $request->email)->first();
            
            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 401);
            }
            
            // Check password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }
            
            // Create token
            $token = $user->createToken('seller-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'token' => $token,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Seller login test error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error in login test',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
