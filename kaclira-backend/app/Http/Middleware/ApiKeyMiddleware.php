<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request for API key authentication.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->extractApiKey($request);
        
        if (!$apiKey) {
            return $this->unauthorizedResponse('API key is required');
        }
        
        $keyRecord = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();
            
        if (!$keyRecord) {
            Log::warning('Invalid API key used', [
                'api_key' => substr($apiKey, 0, 8) . '...',
                'ip' => $request->ip(),
                'endpoint' => $request->fullUrl(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->unauthorizedResponse('Invalid API key');
        }
        
        // Check if API key is expired
        if ($keyRecord->expires_at && $keyRecord->expires_at->isPast()) {
            return $this->unauthorizedResponse('API key has expired');
        }
        
        // Check rate limits for this API key
        if ($this->isRateLimited($keyRecord, $request)) {
            return $this->rateLimitResponse($keyRecord);
        }
        
        // Update last used timestamp and usage count
        $keyRecord->increment('usage_count');
        $keyRecord->update(['last_used_at' => now()]);
        
        // Add API key info to request
        $request->merge(['api_key_record' => $keyRecord]);
        
        $response = $next($request);
        
        // Add API key headers
        $response->headers->set('X-API-Key-Name', $keyRecord->name);
        $response->headers->set('X-API-Key-Usage', $keyRecord->usage_count);
        
        if ($keyRecord->rate_limit_per_hour) {
            $remaining = $this->getRemainingRequests($keyRecord);
            $response->headers->set('X-API-Key-Limit', $keyRecord->rate_limit_per_hour);
            $response->headers->set('X-API-Key-Remaining', $remaining);
        }
        
        return $response;
    }
    
    /**
     * Extract API key from request
     */
    private function extractApiKey(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }
        
        // Check X-API-Key header
        $apiKeyHeader = $request->header('X-API-Key');
        if ($apiKeyHeader) {
            return $apiKeyHeader;
        }
        
        // Check query parameter
        return $request->query('api_key');
    }
    
    /**
     * Check if API key is rate limited
     */
    private function isRateLimited(ApiKey $keyRecord, Request $request): bool
    {
        if (!$keyRecord->rate_limit_per_hour) {
            return false;
        }
        
        $cacheKey = "api_key_usage:{$keyRecord->id}:" . now()->format('Y-m-d-H');
        $currentUsage = cache()->get($cacheKey, 0);
        
        return $currentUsage >= $keyRecord->rate_limit_per_hour;
    }
    
    /**
     * Get remaining requests for API key
     */
    private function getRemainingRequests(ApiKey $keyRecord): int
    {
        if (!$keyRecord->rate_limit_per_hour) {
            return -1; // Unlimited
        }
        
        $cacheKey = "api_key_usage:{$keyRecord->id}:" . now()->format('Y-m-d-H');
        $currentUsage = cache()->get($cacheKey, 0);
        
        return max(0, $keyRecord->rate_limit_per_hour - $currentUsage);
    }
    
    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
            'code' => 'API_KEY_REQUIRED'
        ], 401);
    }
    
    /**
     * Return rate limit response for API key
     */
    private function rateLimitResponse(ApiKey $keyRecord): Response
    {
        $resetTime = now()->addHour()->startOfHour();
        
        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'API key rate limit exceeded',
            'limit' => $keyRecord->rate_limit_per_hour,
            'reset_at' => $resetTime->toISOString(),
            'code' => 'API_KEY_RATE_LIMIT'
        ], 429);
    }
}
