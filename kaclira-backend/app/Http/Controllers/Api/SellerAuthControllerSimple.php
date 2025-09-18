<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SellerAuthControllerSimple extends Controller
{
    /**
     * Login seller and create token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Log request data for debugging
            Log::info('Seller login attempt', [
                'email' => $request->email,
                'has_password' => !empty($request->password),
                'headers' => $request->headers->all()
            ]);

            // Validate request
            try {
                $validated = $request->validate([
                    'email' => 'required|email',
                    'password' => 'required|string',
                ]);
            } catch (ValidationException $e) {
                Log::warning('Seller login validation failed', [
                    'errors' => $e->errors()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            // Find the user
            $user = User::where('email', $request->email)->first();
            
            // Check if user exists
            if (!$user) {
                Log::warning('Seller login failed: User not found', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'These credentials do not match our records.',
                ], 401);
            }
            
            // Check password
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Seller login failed: Invalid password', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'These credentials do not match our records.',
                ], 401);
            }
            
            // Check if user has seller role or any admin role
            if (!$user->hasAnyRole(['seller', 'sub_seller', 'admin', 'super_admin'])) {
                // If user doesn't have the seller role, assign it
                try {
                    $sellerRole = \Spatie\Permission\Models\Role::where('name', 'seller')->first();
                    if (!$sellerRole) {
                        // Create the seller role if it doesn't exist
                        $sellerRole = \Spatie\Permission\Models\Role::create(['name' => 'seller', 'guard_name' => 'web']);
                    }
                    $user->assignRole('seller');
                    
                    Log::info('Seller role assigned to user', ['email' => $request->email, 'user_id' => $user->id]);
                } catch (\Exception $roleException) {
                    Log::error('Failed to assign seller role', [
                        'email' => $request->email,
                        'error' => $roleException->getMessage()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to access the seller dashboard.',
                    ], 403);
                }
            }
            
            // Check if user is active, if not, activate the user
            if (!$user->is_active) {
                $user->is_active = true;
                $user->save();
                Log::info('User activated during login', ['email' => $request->email, 'user_id' => $user->id]);
            }

            // Revoke previous tokens
            $user->tokens()->where('name', 'seller-token')->delete();
            
            // Create new token
            $token = $user->createToken('seller-token')->plainTextToken;
            
            Log::info('Seller login successful', [
                'email' => $request->email, 
                'user_id' => $user->id,
                'token_type' => 'Bearer'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        'is_active' => $user->is_active,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Seller login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }
}
