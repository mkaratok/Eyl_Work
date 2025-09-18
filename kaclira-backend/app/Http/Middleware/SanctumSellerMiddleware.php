<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SanctumSellerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the token from the request
        $token = $request->bearerToken();
        
        if (!$token) {
            Log::warning('SanctumSellerMiddleware: No token provided');
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access - No token provided',
                'data' => null
            ], 401);
        }
        
        try {
            // Manually validate the token
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                Log::warning('SanctumSellerMiddleware: Invalid token');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access - Invalid token',
                    'data' => null
                ], 401);
            }
            
            $user = $accessToken->tokenable;
            
            // Log successful token validation
            Log::info('SanctumSellerMiddleware: Token validated successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            // Check if user is active, if not, activate the user
            if (!$user->is_active) {
                // Auto-activate the user
                $user->is_active = true;
                $user->save();
                
                Log::info('SanctumSellerMiddleware: User account automatically activated', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            }
            
            // Check if user has appropriate role
            if (!$user->hasAnyRole(['seller', 'sub_seller', 'admin', 'super_admin'])) {
                // Assign seller role if missing
                try {
                    $sellerRole = \Spatie\Permission\Models\Role::where('name', 'seller')->first();
                    if (!$sellerRole) {
                        // Create the seller role if it doesn't exist
                        $sellerRole = \Spatie\Permission\Models\Role::create(['name' => 'seller', 'guard_name' => 'web']);
                    }
                    $user->assignRole('seller');
                    
                    Log::info('SanctumSellerMiddleware: Seller role assigned to user', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                } catch (\Exception $roleException) {
                    Log::error('SanctumSellerMiddleware: Failed to assign seller role', [
                        'user_id' => $user->id,
                        'error' => $roleException->getMessage()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to access this resource',
                        'data' => null
                    ], 403);
                }
            }
            
            // Set the authenticated user for this request
            Auth::setUser($user);
            
        } catch (\Exception $e) {
            Log::error('SanctumSellerMiddleware: Exception during token validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Authentication error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
        
        // If we reach here, authentication was successful
        return $next($request);
    }
}
