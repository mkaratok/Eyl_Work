<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request for CORS configuration.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflightRequest($request);
        }

        $response = $next($request);

        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Handle preflight OPTIONS request
     */
    private function handlePreflightRequest(Request $request): Response
    {
        $response = response('', 200);
        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Add CORS headers to response
     */
    private function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->header('Origin');
        $allowedOrigins = $this->getAllowedOrigins();

        // Check if origin is allowed
        if ($this->isOriginAllowed($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 
            'Origin, Content-Type, Accept, Authorization, X-Request-With, X-API-Key, X-Requested-With'
        );
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
        $response->headers->set('Access-Control-Expose-Headers', 
            'X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, X-API-Key-Usage'
        );

        return $response;
    }

    /**
     * Get allowed origins from configuration
     */
    private function getAllowedOrigins(): array
    {
        $allowedOrigins = config('cors.allowed_origins', []);
        
        // Add environment-specific origins
        if (app()->environment('local')) {
            $allowedOrigins = array_merge($allowedOrigins, [
                'http://localhost:3000',
                'http://127.0.0.1:3000',
                'http://localhost:3001',
                'http://127.0.0.1:3001',
                'http://localhost:8080',
                'http://127.0.0.1:8080'
            ]);
        }

        return $allowedOrigins;
    }

    /**
     * Check if origin is allowed
     */
    private function isOriginAllowed(?string $origin, array $allowedOrigins): bool
    {
        if (!$origin) {
            return false;
        }

        // Check for exact match
        if (in_array($origin, $allowedOrigins)) {
            return true;
        }

        // Check for wildcard match
        foreach ($allowedOrigins as $allowedOrigin) {
            if (str_contains($allowedOrigin, '*')) {
                $pattern = str_replace('*', '.*', preg_quote($allowedOrigin, '/'));
                if (preg_match("/^{$pattern}$/", $origin)) {
                    return true;
                }
            }
        }

        return false;
    }
}
