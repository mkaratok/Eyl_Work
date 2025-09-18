<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SellerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        // Get the authenticated user
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        // Check if user is active, if not, activate the user
        if (!$user->is_active) {
            // Auto-activate the user
            $user->is_active = true;
            $user->save();
            
            \Illuminate\Support\Facades\Log::info('SellerMiddleware: User account automatically activated', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }

        // Check if user has seller, sub_seller, admin, or super_admin role
        if (!$user->hasAnyRole(['seller', 'sub_seller', 'admin', 'super_admin'])) {
            // Assign seller role if missing
            try {
                $sellerRole = \Spatie\Permission\Models\Role::where('name', 'seller')->first();
                if (!$sellerRole) {
                    // Create the seller role if it doesn't exist
                    $sellerRole = \Spatie\Permission\Models\Role::create(['name' => 'seller', 'guard_name' => 'web']);
                }
                $user->assignRole('seller');
                
                \Illuminate\Support\Facades\Log::info('SellerMiddleware: Seller role assigned to user', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $roleException) {
                \Illuminate\Support\Facades\Log::error('SellerMiddleware: Failed to assign seller role', [
                    'user_id' => $user->id,
                    'error' => $roleException->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource'
                ], 403);
            }
        }

        // Check specific permission if provided
        if ($permission && !$user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        // Set the authenticated user in the request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}