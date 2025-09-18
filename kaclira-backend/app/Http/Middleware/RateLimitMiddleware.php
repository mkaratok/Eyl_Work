<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Helpers\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Rate limit configurations by user type
     */
    private const RATE_LIMITS = [
        'guest' => ['requests' => 100, 'minutes' => 60],
        'user' => ['requests' => 500, 'minutes' => 60],
        'seller' => ['requests' => 1000, 'minutes' => 60],
        'admin' => ['requests' => 0, 'minutes' => 60], // Unlimited
    ];

    /**
     * Handle an incoming request for Kaçlıra.com API rate limiting.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $key = 'api'): Response
    {
        $userType = $this->getUserType($request);
        $limits = self::RATE_LIMITS[$userType];
        
        // Admin has unlimited access
        if ($userType === 'admin') {
            return $next($request);
        }
        
        $maxAttempts = $limits['requests'];
        $decayMinutes = $limits['minutes'];
        
        $rateLimitKey = $this->getRateLimitKey($request, $key, $userType);
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            
            // Log rate limit exceeded
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
                'user_type' => $userType,
                'endpoint' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'retry_after' => $seconds
            ]);
            
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => "Rate limit exceeded for {$userType}. Try again in {$seconds} seconds.",
                'retry_after' => $seconds,
                'limit' => $maxAttempts,
                'window' => $decayMinutes . ' minutes'
            ], 429);
        }
        
        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);
        
        $response = $next($request);
        
        // Add comprehensive rate limit headers
        $remaining = RateLimiter::remaining($rateLimitKey, $maxAttempts);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);
        $response->headers->set('X-RateLimit-User-Type', $userType);
        
        return $response;
    }

    /**
     * Determine user type for rate limiting
     */
    private function getUserType(Request $request): string
    {
        if (!Auth::check()) {
            return 'guest';
        }

        $user = Auth::user();
        
        if ($user->hasRole('admin')) {
            return 'admin';
        }
        
        if ($user->hasRole('seller')) {
            return 'seller';
        }
        
        return 'user';
    }

    /**
     * Generate rate limit key
     */
    private function getRateLimitKey(Request $request, string $key, string $userType): string
    {
        $identifier = Auth::check() ? Auth::id() : $request->ip();
        return "{$key}:{$userType}:{$identifier}";
    }
}
