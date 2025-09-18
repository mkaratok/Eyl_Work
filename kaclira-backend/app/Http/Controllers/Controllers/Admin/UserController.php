<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Get users list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'nullable|string|in:user,seller,admin,super_admin',
                'status' => 'nullable|string|in:active,inactive,banned',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:name,email,created_at,last_login_at',
                'sort_order' => 'nullable|string|in:asc,desc'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $validator->validated();
            $data = $this->adminService->getUserManagementData($filters);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Users retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('User management error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı listesi alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get specific user details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = User::with([
                'favorites.product',
                'notifications' => function ($query) {
                    $query->latest()->limit(10);
                }
            ])->findOrFail($id);

            $stats = [
                'favorites_count' => $user->favorites()->count(),
                'notifications_count' => $user->notifications()->count(),
                'unread_notifications' => $user->notifications()->whereNull('read_at')->count(),
                'last_activity' => $user->last_login_at,
                'account_age' => $user->created_at->diffForHumans(),
                'profile_completion' => $this->calculateProfileCompletion($user)
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'stats' => $stats
                ],
                'message' => 'User details retrieved successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('User show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı detayları alınırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|string|in:user,seller,admin',
                'phone' => 'nullable|string|max:20',
                'is_active' => 'nullable|boolean',
                'email_verified' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['password'] = Hash::make($data['password']);
            $data['is_active'] = $data['is_active'] ?? true;
            
            if ($data['email_verified'] ?? false) {
                $data['email_verified_at'] = now();
            }

            $user = User::create($data);
            $user->assignRole($data['role']);

            return response()->json([
                'success' => true,
                'data' => $user->load('roles'),
                'message' => 'Kullanıcı başarıyla oluşturuldu'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('User creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı oluşturulurken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)
                ],
                'role' => 'sometimes|required|string|in:user,seller,admin',
                'phone' => 'nullable|string|max:20',
                'is_active' => 'nullable|boolean',
                'email_verified' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if (isset($data['email_verified'])) {
                if ($data['email_verified'] && !$user->email_verified_at) {
                    $data['email_verified_at'] = now();
                } elseif (!$data['email_verified']) {
                    $data['email_verified_at'] = null;
                }
                unset($data['email_verified']);
            }

            if (isset($data['role']) && $data['role'] !== $user->getRoleNames()->first()) {
                $user->syncRoles([$data['role']]);
                unset($data['role']);
            }

            $user->update($data);

            return response()->json([
                'success' => true,
                'data' => $user->load('roles'),
                'message' => 'Kullanıcı başarıyla güncellendi'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('User update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı güncellenirken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Super admin kullanıcısı silinemez'
                ], 403);
            }

            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kendi hesabınızı silemezsiniz'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı başarıyla silindi'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('User deletion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı silinirken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Ban/Unban user
     */
    public function toggleBan(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'banned' => 'required|boolean',
                'ban_reason' => 'nullable|string|max:500',
                'ban_expires_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($data['banned']) {
                $user->update([
                    'banned_at' => now(),
                    'ban_reason' => $data['ban_reason'] ?? null,
                    'ban_expires_at' => $data['ban_expires_at'] ?? null,
                    'banned_by' => auth()->id()
                ]);
                $message = 'Kullanıcı başarıyla yasaklandı';
            } else {
                $user->update([
                    'banned_at' => null,
                    'ban_reason' => null,
                    'ban_expires_at' => null,
                    'banned_by' => null
                ]);
                $message = 'Kullanıcı yasağı kaldırıldı';
            }

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('User ban toggle error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı yasaklama durumu değiştirilirken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed',
                'send_notification' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->password),
                'password_changed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı şifresi başarıyla sıfırlandı'
            ]);

        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Şifre sıfırlanırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Calculate profile completion percentage
     */
    protected function calculateProfileCompletion(User $user): int
    {
        $fields = ['name', 'email', 'phone', 'avatar'];
        $completed = 0;
        
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }
        
        if ($user->email_verified_at) {
            $completed++;
        }
        
        return round(($completed / (count($fields) + 1)) * 100);
    }
}
