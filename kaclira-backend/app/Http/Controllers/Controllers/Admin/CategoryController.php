<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
        $this->middleware(['auth:api', 'admin']);
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
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $category = $this->categoryService->createCategory($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
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

        $category = $this->categoryService->updateCategory($category, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->deleteCategory($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    public function sync(): JsonResponse
    {
        $stats = $this->categoryService->syncGoogleCategories();

        return response()->json([
            'success' => true,
            'message' => 'Google categories synced successfully',
            'data' => $stats
        ]);
    }
}
