<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AdminAuthController extends BaseController
{
    /**
     * Admin login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $credentials = $request->only('email', 'password');

        // Attempt to authenticate the user
        if (!Auth::attempt($credentials)) {
            return $this->sendError('Invalid credentials', [], 401);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return $this->sendError('Account disabled', [], 403);
        }

        // Check if user has admin role
        if (!$user->hasAnyRole(['admin', 'super_admin'])) {
            Auth::logout();
            return $this->sendError('Admin access required', [], 403);
        }

        // Create token for Sanctum authentication
        $token = $user->createToken('admin-token')->plainTextToken;

        // Log the authentication for debugging
        \Log::info('Admin login successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => $user->getRoleNames()
        ]);

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token
        ];

        return $this->sendResponse($data, 'Admin logged in successfully');
    }

    /**
     * Get the authenticated admin user
     */
    public function me()
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->sendError('User not found', [], 401);
        }

        // Verify the user still has admin role
        if (!$user->hasAnyRole(['admin', 'super_admin'])) {
            return $this->sendError('Admin access required', [], 403);
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

        return $this->sendResponse($data, 'Admin profile retrieved successfully');
    }

    /**
     * Refresh admin token
     */
    public function refresh()
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->sendError('User not found', [], 401);
        }

        // Create new token
        $token = $user->createToken('admin-token')->plainTextToken;

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token
        ];

        return $this->sendResponse($data, 'Authentication refreshed successfully');
    }

    /**
     * Admin logout
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse([], 'Admin logged out successfully');
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            $user = Auth::user();
            $user->update($request->only(['name', 'phone', 'avatar']));

            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'roles' => $user->getRoleNames(),
            ];

            return $this->sendResponse($data, 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Could not update profile', [], 500);
        }
    }

    /**
     * Change admin password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            $user = Auth::user();

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Current password is incorrect', [], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return $this->sendResponse([], 'Password changed successfully');
        } catch (\Exception $e) {
            return $this->sendError('Could not change password', [], 500);
        }
    }
}