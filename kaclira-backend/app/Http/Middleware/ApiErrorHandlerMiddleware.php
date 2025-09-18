<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiErrorHandlerMiddleware
{
    /**
     * Handle an incoming request and catch exceptions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
            
            // Transform non-JSON responses to standardized format
            if ($request->expectsJson() && !$response instanceof JsonResponse) {
                return $this->transformResponse($response);
            }
            
            return $response;
            
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle exceptions and return standardized error response
     */
    private function handleException(Request $request, Throwable $e): JsonResponse
    {
        $statusCode = $this->getStatusCode($e);
        $errorCode = $this->getErrorCode($e);
        $message = $this->getErrorMessage($e);
        
        // Log error details
        Log::error('API Exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'input' => $request->except(['password', 'password_confirmation', 'token'])
            ]
        ]);

        $errorResponse = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'status' => $statusCode
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => $request->header('X-Request-ID') ?: uniqid(),
                'api_version' => $request->get('api_version', 'v1')
            ]
        ];

        // Add debug information in development
        if (app()->environment(['local', 'testing'])) {
            $errorResponse['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace()
            ];
        }

        return response()->json($errorResponse, $statusCode);
    }

    /**
     * Transform successful response to standardized format
     */
    private function transformResponse(Response $response): JsonResponse
    {
        $content = $response->getContent();
        $data = json_decode($content, true) ?? $content;

        $standardResponse = [
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'status' => $response->getStatusCode()
            ]
        ];

        return response()->json($standardResponse, $response->getStatusCode());
    }

    /**
     * Get HTTP status code from exception
     */
    private function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        return match(get_class($e)) {
            'Illuminate\Auth\AuthenticationException' => 401,
            'Illuminate\Auth\Access\AuthorizationException' => 403,
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 404,
            'Illuminate\Validation\ValidationException' => 422,
            'Illuminate\Database\QueryException' => 500,
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 404,
            'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException' => 405,
            'Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException' => 429,
            default => 500
        };
    }

    /**
     * Get error code from exception
     */
    private function getErrorCode(Throwable $e): string
    {
        return match(get_class($e)) {
            'Illuminate\Auth\AuthenticationException' => 'UNAUTHENTICATED',
            'Illuminate\Auth\Access\AuthorizationException' => 'UNAUTHORIZED',
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 'RESOURCE_NOT_FOUND',
            'Illuminate\Validation\ValidationException' => 'VALIDATION_ERROR',
            'Illuminate\Database\QueryException' => 'DATABASE_ERROR',
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 'ENDPOINT_NOT_FOUND',
            'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException' => 'METHOD_NOT_ALLOWED',
            'Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException' => 'RATE_LIMIT_EXCEEDED',
            default => 'INTERNAL_SERVER_ERROR'
        };
    }

    /**
     * Get user-friendly error message
     */
    private function getErrorMessage(Throwable $e): string
    {
        if (app()->environment('production')) {
            return match(get_class($e)) {
                'Illuminate\Auth\AuthenticationException' => 'Authentication required',
                'Illuminate\Auth\Access\AuthorizationException' => 'Insufficient permissions',
                'Illuminate\Database\Eloquent\ModelNotFoundException' => 'Resource not found',
                'Illuminate\Validation\ValidationException' => $e->getMessage(),
                'Illuminate\Database\QueryException' => 'Database operation failed',
                'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 'Endpoint not found',
                'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException' => 'HTTP method not allowed',
                'Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException' => 'Too many requests',
                default => 'An unexpected error occurred'
            };
        }

        return $e->getMessage();
    }
}
