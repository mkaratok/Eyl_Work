<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get user's API keys
     */
    public function index(Request $request): JsonResponse
    {
        $apiKeys = ApiKey::forUser(auth()->id())
            ->select(['id', 'name', 'permissions', 'rate_limit_per_hour', 'is_active', 'expires_at', 'last_used_at', 'usage_count', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                $key->masked_key = $key->masked_key;
                return $key;
            });

        return ApiResponse::success($apiKeys, 'API keys retrieved successfully');
    }

    /**
     * Create new API key
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:read,write,delete,admin',
            'rate_limit_per_hour' => 'nullable|integer|min:1|max:10000',
            'expires_at' => 'nullable|date|after:now',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'string|max:255',
        ]);

        // Check user's API key limit
        $userKeyCount = ApiKey::forUser(auth()->id())->active()->count();
        $maxKeys = config('api.security.api_keys.max_keys_per_user', 5);

        if ($userKeyCount >= $maxKeys) {
            return ApiResponse::error(
                "Maximum number of API keys ({$maxKeys}) reached",
                400,
                'API_KEY_LIMIT_EXCEEDED'
            );
        }

        $apiKey = ApiKey::createForUser(auth()->id(), $validated);

        return ApiResponse::created([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $apiKey->key, // Only show full key on creation
            'permissions' => $apiKey->permissions,
            'rate_limit_per_hour' => $apiKey->rate_limit_per_hour,
            'expires_at' => $apiKey->expires_at,
            'created_at' => $apiKey->created_at,
        ], 'API key created successfully');
    }

    /**
     * Show API key details
     */
    public function show(int $id): JsonResponse
    {
        $apiKey = ApiKey::forUser(auth()->id())->findOrFail($id);

        return ApiResponse::resource([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'masked_key' => $apiKey->masked_key,
            'permissions' => $apiKey->permissions,
            'rate_limit_per_hour' => $apiKey->rate_limit_per_hour,
            'is_active' => $apiKey->is_active,
            'expires_at' => $apiKey->expires_at,
            'last_used_at' => $apiKey->last_used_at,
            'usage_count' => $apiKey->usage_count,
            'allowed_ips' => $apiKey->allowed_ips,
            'allowed_domains' => $apiKey->allowed_domains,
            'created_at' => $apiKey->created_at,
            'updated_at' => $apiKey->updated_at,
        ], 'API key retrieved successfully');
    }

    /**
     * Update API key
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::forUser(auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|in:read,write,delete,admin',
            'rate_limit_per_hour' => 'sometimes|integer|min:1|max:10000',
            'expires_at' => 'sometimes|nullable|date|after:now',
            'allowed_ips' => 'sometimes|nullable|array',
            'allowed_ips.*' => 'ip',
            'allowed_domains' => 'sometimes|nullable|array',
            'allowed_domains.*' => 'string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $apiKey->update($validated);

        return ApiResponse::success([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'masked_key' => $apiKey->masked_key,
            'permissions' => $apiKey->permissions,
            'rate_limit_per_hour' => $apiKey->rate_limit_per_hour,
            'is_active' => $apiKey->is_active,
            'expires_at' => $apiKey->expires_at,
            'updated_at' => $apiKey->updated_at,
        ], 'API key updated successfully');
    }

    /**
     * Regenerate API key
     */
    public function regenerate(int $id): JsonResponse
    {
        $apiKey = ApiKey::forUser(auth()->id())->findOrFail($id);
        $newKey = $apiKey->regenerate();

        return ApiResponse::success([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $newKey, // Show full key on regeneration
            'masked_key' => $apiKey->masked_key,
            'regenerated_at' => now(),
        ], 'API key regenerated successfully');
    }

    /**
     * Revoke API key
     */
    public function revoke(int $id): JsonResponse
    {
        $apiKey = ApiKey::forUser(auth()->id())->findOrFail($id);
        $apiKey->revoke();

        return ApiResponse::success([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'revoked_at' => now(),
        ], 'API key revoked successfully');
    }

    /**
     * Delete API key
     */
    public function destroy(int $id): JsonResponse
    {
        $apiKey = ApiKey::forUser(auth()->id())->findOrFail($id);
        $apiKey->delete();

        return ApiResponse::noContent('API key deleted successfully');
    }

    /**
     * Get API key usage statistics
     */
    public function usage(int $id): JsonResponse
    {
        $apiKey = ApiKey::forUser(auth()->id())->findOrFail($id);

        $stats = [
            'total_requests' => $apiKey->usage_count,
            'last_used' => $apiKey->last_used_at,
            'created' => $apiKey->created_at,
            'status' => $apiKey->is_active ? 'active' : 'inactive',
            'expires' => $apiKey->expires_at,
            'is_expired' => $apiKey->isExpired(),
            'rate_limit' => $apiKey->rate_limit_per_hour,
            'permissions' => $apiKey->permissions,
        ];

        return ApiResponse::success($stats, 'API key usage retrieved successfully');
    }
}
