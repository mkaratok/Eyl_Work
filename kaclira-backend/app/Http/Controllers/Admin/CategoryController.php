<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $categories = Category::with(['parent', 'children'])
            ->when($request->search, fn($q) => $q->search($request->search))
            ->orderBy('path')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin category creation failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        try {
            $category = $this->categoryService->createCategory($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Category creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'name' => 'string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        try {
            $category = $this->categoryService->updateCategory($category, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            \Log::error('Category update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'category_id' => $category->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($category);

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Category deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'category_id' => $category->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sync(): JsonResponse
    {
        try {
            $stats = $this->categoryService->syncGoogleCategories();

            return response()->json([
                'success' => true,
                'message' => 'Google categories synced successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Category sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}