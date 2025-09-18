<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SellerAuthController extends BaseController
{
    /**
     * Seller login using Sanctum
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors());
        }

        try {
            // Find user by email
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return $this->sendError('User not found', [], 401);
            }
            
            // Check password
            if (!Hash::check($request->password, $user->password)) {
                return $this->sendError('Invalid credentials', [], 401);
            }
            
            // Check if user has seller role
            if (!$user->hasAnyRole(['seller', 'sub_seller', 'admin', 'super_admin'])) {
                return $this->sendError('Seller access required', [], 403);
            }
            
            // Check if user is active
            if (!$user->is_active) {
                return $this->sendError('Account disabled', [], 403);
            }
            
            // Create Sanctum token
            $token = $user->createToken('seller-token')->plainTextToken;

            $data = [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ]
            ];

            return $this->sendResponse($data, 'Seller logged in successfully');
            
        } catch (\Exception $e) {
            Log::error('Seller login error: ' . $e->getMessage());
            return $this->sendError('Authentication error: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Seller registration
     */
    public function register(Request $request)
    {
        // Log the registration attempt for debugging
        \Illuminate\Support\Facades\Log::info('Seller registration attempt', [
            'name' => $request->name,
            'email' => $request->email,
            'has_phone' => !empty($request->phone),
            'headers' => $request->headers->all()
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('Seller registration validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return $this->sendValidationError($validator->errors());
        }

        try {
            // Start a database transaction
            \DB::beginTransaction();
            
            \Illuminate\Support\Facades\Log::info('Creating new seller user', [
                'name' => $request->name,
                'email' => $request->email
            ]);
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => true, // Set to true for immediate access (can be changed to false if admin approval is required)
            ]);

            // Check if the seller role exists
            $sellerRole = \Spatie\Permission\Models\Role::where('name', 'seller')->first();
            if (!$sellerRole) {
                // Create the seller role if it doesn't exist
                \Illuminate\Support\Facades\Log::info('Creating seller role as it does not exist');
                $sellerRole = \Spatie\Permission\Models\Role::create(['name' => 'seller', 'guard_name' => 'web']);
            }

            // Assign seller role
            \Illuminate\Support\Facades\Log::info('Assigning seller role to user', ['user_id' => $user->id]);
            $user->assignRole('seller');
            
            // Commit the transaction
            \DB::commit();

            // Create a token for immediate login
            \Illuminate\Support\Facades\Log::info('Creating authentication token for new seller', ['user_id' => $user->id]);
            $token = $user->createToken('seller-token')->plainTextToken;

            $data = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'roles' => $user->getRoleNames(),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ];

            \Illuminate\Support\Facades\Log::info('Seller registration successful', ['user_id' => $user->id]);
            return $this->sendResponse($data, 'Seller registered successfully.');
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            \DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Seller registration failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Registration failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get the authenticated seller user
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->sendError('User not found', [], 401);
            }

            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'created_at' => $user->created_at,
            ];

            return $this->sendResponse($data, 'Seller profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Authentication error: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Logout and revoke token
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user) {
                // Revoke current access token
                $user->currentAccessToken()->delete();
                
                return $this->sendResponse([], 'Seller logged out successfully');
            }
            
            return $this->sendError('User not authenticated', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Logout failed: ' . $e->getMessage(), [], 500);
        }
    }
}
