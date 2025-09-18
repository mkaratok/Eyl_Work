<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    /**
     * Return a successful response
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'status' => $statusCode,
                'version' => request()->get('api_version', 'v1')
            ]
        ];

        // Handle pagination
        if ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['meta']['pagination'] = [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages()
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error response
     */
    public static function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        string $errorCode = 'GENERIC_ERROR',
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'status' => $statusCode
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?: uniqid(),
                'version' => request()->get('api_version', 'v1')
            ]
        ];

        if (!empty($errors)) {
            $response['error']['details'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, 422, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Return an unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401, 'UNAUTHORIZED');
    }

    /**
     * Return a forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403, 'FORBIDDEN');
    }

    /**
     * Return a not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404, 'NOT_FOUND');
    }

    /**
     * Return a server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500, 'INTERNAL_SERVER_ERROR');
    }

    /**
     * Return a rate limit exceeded response
     */
    public static function rateLimitExceeded(int $retryAfter = 60): JsonResponse
    {
        $response = self::error(
            'Rate limit exceeded',
            429,
            'RATE_LIMIT_EXCEEDED'
        );

        $response->header('Retry-After', $retryAfter);
        
        return $response;
    }

    /**
     * Return a created response
     */
    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Return a no content response
     */
    public static function noContent(string $message = 'Operation completed successfully'): JsonResponse
    {
        return self::success(null, $message, 204);
    }

    /**
     * Return a paginated response
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return self::success($paginator, $message);
    }

    /**
     * Return a collection response
     */
    public static function collection($data, string $message = 'Data retrieved successfully', array $meta = []): JsonResponse
    {
        $response = self::success($data, $message);
        
        if (!empty($meta)) {
            $responseData = $response->getData(true);
            $responseData['meta'] = array_merge($responseData['meta'], $meta);
            $response->setData($responseData);
        }
        
        return $response;
    }

    /**
     * Return a resource response
     */
    public static function resource($data, string $message = 'Resource retrieved successfully'): JsonResponse
    {
        return self::success($data, $message);
    }
}
