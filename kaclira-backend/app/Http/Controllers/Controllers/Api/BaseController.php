<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response method for Kaçlıra.com API
     *
     * @param mixed $result
     * @param string $message
     * @return JsonResponse
     */
    public function sendResponse($result, string $message = 'Success'): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $result,
        ];

        return response()->json($response, 200);
    }

    /**
     * Error response method for Kaçlıra.com API
     *
     * @param string $error
     * @param array $errorMessages
     * @param int $code
     * @return JsonResponse
     */
    public function sendError(string $error, array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Validation error response method
     *
     * @param array $errors
     * @return JsonResponse
     */
    public function sendValidationError(array $errors): JsonResponse
    {
        return $this->sendError('Validation Error', $errors, 422);
    }

    /**
     * Unauthorized response method
     *
     * @param string $message
     * @return JsonResponse
     */
    public function sendUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->sendError($message, [], 401);
    }
}
