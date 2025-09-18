<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends BaseController
{
    /**
     * User registration for Kaçlıra.com
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        // Authenticate the user after registration
        Auth::login($user);

        return $this->sendResponse([
            'user' => $user,
            'token_type' => 'bearer',
        ], 'User registered successfully');
    }

    /**
     * User login for Kaçlıra.com
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return $this->sendUnauthorized('Invalid credentials');
        }

        // Get the authenticated user
        $user = Auth::user();

        // Load user roles and permissions
        $userData = [
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

        return $this->sendResponse([
            'user' => $userData,
            'token_type' => 'bearer',
        ], 'Login successful');
    }

    /**
     * Get authenticated user profile
     *
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->sendUnauthorized('Unauthorized');
        }

        return $this->sendResponse($user, 'User profile retrieved successfully');
    }

    /**
     * Refresh authentication (not applicable for session auth)
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->sendUnauthorized('Unauthorized');
        }

        return $this->sendResponse([
            'user' => $user,
            'token_type' => 'bearer',
        ], 'Authentication refreshed successfully');
    }

    /**
     * User logout
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        Auth::logout();

        return $this->sendResponse([], 'Successfully logged out');
    }
}
