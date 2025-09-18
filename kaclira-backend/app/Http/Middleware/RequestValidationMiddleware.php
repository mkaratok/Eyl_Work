<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class RequestValidationMiddleware
{
    /**
     * Handle an incoming request for validation and security.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate request size
        if (!$this->validateRequestSize($request)) {
            return ApiResponse::error(
                'Request size exceeds maximum allowed limit',
                413,
                'REQUEST_TOO_LARGE'
            );
        }

        // Validate content type for POST/PUT/PATCH requests
        if (!$this->validateContentType($request)) {
            return ApiResponse::error(
                'Invalid content type. Expected application/json',
                415,
                'UNSUPPORTED_MEDIA_TYPE'
            );
        }

        // Validate JSON for JSON requests
        if (!$this->validateJson($request)) {
            return ApiResponse::error(
                'Invalid JSON format in request body',
                400,
                'INVALID_JSON'
            );
        }

        // Check for SQL injection attempts
        if (!$this->validateSqlInjection($request)) {
            $this->logSecurityViolation($request, 'SQL_INJECTION_ATTEMPT');
            return ApiResponse::error(
                'Request contains potentially malicious content',
                400,
                'MALICIOUS_REQUEST'
            );
        }

        // Check for XSS attempts
        if (!$this->validateXss($request)) {
            $this->logSecurityViolation($request, 'XSS_ATTEMPT');
            return ApiResponse::error(
                'Request contains potentially malicious content',
                400,
                'MALICIOUS_REQUEST'
            );
        }

        // Validate required headers
        if (!$this->validateHeaders($request)) {
            return ApiResponse::error(
                'Missing required headers',
                400,
                'MISSING_HEADERS'
            );
        }

        // Sanitize input data
        $this->sanitizeInput($request);

        return $next($request);
    }

    /**
     * Validate request size
     */
    private function validateRequestSize(Request $request): bool
    {
        $maxSize = $this->parseSize(config('api.security.request_validation.max_request_size', '10M'));
        $contentLength = $request->header('Content-Length', 0);
        
        return $contentLength <= $maxSize;
    }

    /**
     * Validate content type
     */
    private function validateContentType(Request $request): bool
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return true;
        }

        $contentType = $request->header('Content-Type');
        
        // Allow multipart/form-data for file uploads
        if (str_starts_with($contentType, 'multipart/form-data')) {
            return true;
        }

        // Require application/json for other requests
        return str_starts_with($contentType, 'application/json');
    }

    /**
     * Validate JSON format
     */
    private function validateJson(Request $request): bool
    {
        if (!config('api.security.request_validation.validate_json', true)) {
            return true;
        }

        if (!$request->isJson()) {
            return true;
        }

        $content = $request->getContent();
        if (empty($content)) {
            return true;
        }

        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate against SQL injection
     */
    private function validateSqlInjection(Request $request): bool
    {
        if (!config('api.security.sql_injection.enabled', true)) {
            return true;
        }

        $suspiciousPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\bSCRIPT\b.*>)/i',
            '/(\'.*\bOR\b.*\')/i',
            '/(\".*\bOR\b.*\")/i',
            '/(\-\-)/i',
            '/(\/\*.*\*\/)/i',
        ];

        $allInput = array_merge($request->all(), [$request->getContent()]);
        
        foreach ($allInput as $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Validate against XSS
     */
    private function validateXss(Request $request): bool
    {
        if (!config('api.security.xss_protection.enabled', true)) {
            return true;
        }

        $suspiciousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/javascript:/i',
            '/vbscript:/i',
            '/data:text\/html/i',
        ];

        $allInput = array_merge($request->all(), [$request->getContent()]);
        
        foreach ($allInput as $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Validate required headers
     */
    private function validateHeaders(Request $request): bool
    {
        if (!config('api.security.request_validation.validate_headers', true)) {
            return true;
        }

        $requiredHeaders = ['Accept'];
        
        foreach ($requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitize input data
     */
    private function sanitizeInput(Request $request): void
    {
        if (!config('api.security.xss_protection.sanitize_input', true)) {
            return;
        }

        $input = $request->all();
        $sanitized = $this->recursiveSanitize($input);
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data
     */
    private function recursiveSanitize($data): mixed
    {
        if (is_array($data)) {
            return array_map([$this, 'recursiveSanitize'], $data);
        }

        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }

        return $data;
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize(string $size): int
    {
        $units = ['B' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824];
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        
        return $value * ($units[$unit] ?? 1);
    }

    /**
     * Log security violation
     */
    private function logSecurityViolation(Request $request, string $type): void
    {
        Log::warning('API Security Violation', [
            'type' => $type,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'input' => $request->except(['password', 'password_confirmation', 'token']),
            'headers' => $request->headers->all(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
