<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionMiddleware
{
    /**
     * Supported API versions
     */
    private const SUPPORTED_VERSIONS = ['v1', 'v2'];
    private const DEFAULT_VERSION = 'v1';
    private const LATEST_VERSION = 'v2';

    /**
     * Handle an incoming request for API versioning.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $this->getApiVersion($request);
        
        // Validate version
        if (!in_array($version, self::SUPPORTED_VERSIONS)) {
            return response()->json([
                'error' => 'Unsupported API Version',
                'message' => "API version '{$version}' is not supported.",
                'supported_versions' => self::SUPPORTED_VERSIONS,
                'latest_version' => self::LATEST_VERSION,
                'code' => 'UNSUPPORTED_API_VERSION'
            ], 400);
        }

        // Add version to request
        $request->merge(['api_version' => $version]);
        
        $response = $next($request);
        
        // Add version headers
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-API-Latest-Version', self::LATEST_VERSION);
        $response->headers->set('X-API-Supported-Versions', implode(', ', self::SUPPORTED_VERSIONS));
        
        // Add deprecation warning for old versions
        if ($version !== self::LATEST_VERSION) {
            $response->headers->set('X-API-Deprecation-Warning', 
                "API version {$version} is deprecated. Please upgrade to {self::LATEST_VERSION}."
            );
        }
        
        return $response;
    }

    /**
     * Get API version from request
     */
    private function getApiVersion(Request $request): string
    {
        // Check Accept header (e.g., application/vnd.kaclira.v2+json)
        $acceptHeader = $request->header('Accept');
        if ($acceptHeader && preg_match('/application\/vnd\.kaclira\.(v\d+)\+json/', $acceptHeader, $matches)) {
            return $matches[1];
        }

        // Check X-API-Version header
        $versionHeader = $request->header('X-API-Version');
        if ($versionHeader) {
            return $versionHeader;
        }

        // Check URL path (e.g., /api/v2/products)
        $pathSegments = explode('/', trim($request->path(), '/'));
        if (count($pathSegments) >= 2 && in_array($pathSegments[1], self::SUPPORTED_VERSIONS)) {
            return $pathSegments[1];
        }

        // Check query parameter
        $queryVersion = $request->query('version');
        if ($queryVersion) {
            return $queryVersion;
        }

        return self::DEFAULT_VERSION;
    }

    /**
     * Get version-specific controller namespace
     */
    public static function getControllerNamespace(string $version): string
    {
        return match($version) {
            'v1' => 'App\\Http\\Controllers\\Api\\V1',
            'v2' => 'App\\Http\\Controllers\\Api\\V2',
            default => 'App\\Http\\Controllers\\Api\\V1'
        };
    }
}
