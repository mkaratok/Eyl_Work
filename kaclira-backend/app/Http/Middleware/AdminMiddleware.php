<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        \Log::info('AdminMiddleware: Processing request', [
            'url' => $request->url(),
            'method' => $request->method(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'headers' => array_filter($request->headers->all(), function($key) {
                return in_array($key, ['cookie', 'x-xsrf-token', 'x-requested-with']);
            }, ARRAY_FILTER_USE_KEY)
        ]);
        
        // Make sure session is started
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }
        
        // Start the session if not already started
        if ($request->hasSession() && !$request->session()->isStarted()) {
            $request->session()->start();
        }
        
        // Try different authentication methods
        $user = null;
        
        // Try default guard first
        $user = Auth::user();
        
        // If not found, try the admin guard specifically
        if (!$user) {
            $user = Auth::guard('admin')->user();
        }
        
        // If still not found, try the web guard
        if (!$user) {
            $user = Auth::guard('web')->user();
        }
        
        // If still not found, try to get user from session
        if (!$user) {
            // Check if we have a user ID in the session
            $userId = $request->session()->get('login_web_59ba36addc2b2f9401580f014c7f58ea6e380d3d', null);
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    Auth::login($user);
                }
            }
        }
        
        // If still not found, try the old session key
        if (!$user) {
            $userId = $request->session()->get('user_id');
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    Auth::login($user);
                }
            }
        }
        
        \Log::info('AdminMiddleware: Auth status', [
            'admin_user' => Auth::guard('admin')->user() ? Auth::guard('admin')->user()->id : null,
            'web_user' => Auth::guard('web')->user() ? Auth::guard('web')->user()->id : null,
            'default_user' => Auth::user() ? Auth::user()->id : null,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null
        ]);
        
        if (!$user) {
            \Log::warning('AdminMiddleware: No authenticated user found');
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'User not found'
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            \Log::warning('AdminMiddleware: User account is disabled', ['user_id' => $user->id]);
            return response()->json([
                'error' => 'Account Disabled',
                'message' => 'Your account has been disabled'
            ], 403);
        }

        // Check if user has admin or super_admin role
        try {
            $hasAdminRole = $user->hasAnyRole(['admin', 'super_admin']);
            \Log::info('AdminMiddleware: Role check result', [
                'user_id' => $user->id,
                'has_admin_role' => $hasAdminRole,
                'user_roles' => $user->getRoleNames()->toArray()
            ]);
            
            if (!$hasAdminRole) {
                \Log::warning('AdminMiddleware: User lacks admin role', [
                    'user_id' => $user->id,
                    'roles' => $user->getRoleNames()->toArray()
                ]);
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Admin access required'
                ], 403);
            }
        } catch (\Exception $e) {
            \Log::error('AdminMiddleware: Role check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Internal Error',
                'message' => 'Role check failed'
            ], 500);
        }

        // Check specific permission if provided
        if ($permission) {
            try {
                $hasPermission = $user->can($permission);
                \Log::info('AdminMiddleware: Permission check result', [
                    'user_id' => $user->id,
                    'permission' => $permission,
                    'has_permission' => $hasPermission
                ]);
                
                if (!$hasPermission) {
                    \Log::warning('AdminMiddleware: User lacks required permission', [
                        'user_id' => $user->id,
                        'permission' => $permission
                    ]);
                    return response()->json([
                        'error' => 'Forbidden',
                        'message' => 'Insufficient permissions'
                    ], 403);
                }
            } catch (\Exception $e) {
                \Log::error('AdminMiddleware: Permission check failed', [
                    'user_id' => $user->id,
                    'permission' => $permission,
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'error' => 'Internal Error',
                    'message' => 'Permission check failed'
                ], 500);
            }
        }

        \Log::info('AdminMiddleware: Access granted', ['user_id' => $user->id]);
        
        // Set the authenticated user in the request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}