<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
        // Remove the incorrect middleware call
    }

    /**
     * Get users list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin user list failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }
        
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
            
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            if (isset($data['email_verified']) && $data['email_verified']) {
                $data['email_verified_at'] = now();
            }

            $user->update($data);
            
            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

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
            
            // Prevent deleting super admin
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Super admin kullanıcı silinemez'
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
            \Log::error('User delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı silinirken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle user ban status
     */
    public function toggleBan(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent banning super admin
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Super admin kullanıcı yasaklanamaz'
                ], 403);
            }
            
            $user->is_active = !$user->is_active;
            $user->save();

            $message = $user->is_active ? 'Kullanıcı yasağı kaldırıldı' : 'Kullanıcı yasaklandı';

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => $message
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('User ban toggle error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı yasak durumu değiştirilirken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(int $id, Request $request): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $user->password = Hash::make($data['password']);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Şifre başarıyla sıfırlandı'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('User password reset error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Şifre sıfırlanırken hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk actions on users
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|string|in:delete,ban,unban,assign_role',
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'integer|exists:users,id',
                'role' => 'required_if:action,assign_role|string|in:user,seller,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $result = $this->adminService->bulkUserAction($data);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Toplu işlem başarıyla tamamlandı'
            ]);

        } catch (\Exception $e) {
            \Log::error('User bulk action error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Toplu işlem sırasında hata oluştu',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(User $user): int
    {
        $fields = [
            'name' => !empty($user->name),
            'email' => !empty($user->email),
            'phone' => !empty($user->phone),
            'avatar' => !empty($user->avatar),
            'email_verified_at' => !empty($user->email_verified_at)
        ];

        $completed = array_sum($fields);
        $total = count($fields);

        return $total > 0 ? intval(($completed / $total) * 100) : 0;
    }
}