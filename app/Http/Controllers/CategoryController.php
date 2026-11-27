<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * 1. List all categories with Cache Versioning
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        // Cache versioning (Same pattern as PetType)
        $version = Cache::get('categories_cache_version', 1);
        $cacheKey = "categories_v{$version}_page_{$page}_limit_{$perPage}";

        $categories = Cache::remember($cacheKey, 86400, function () use ($perPage) {
            return Category::select('id', 'name', 'slug', 'image_path', 'created_at')
                ->latest()
                ->paginate($perPage);
        });

        return response()->json($categories);
    }

    /**
     * 2. Store a new category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp,jfif', 'max:2048'], 
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'image_path' => $imagePath,
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $category
        ], 201);
    }

    /**
     * 3. Show single category
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => $category
        ]);
    }

    /**
     * 4. Update specific category
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp,jfif', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // Purani image delete
            if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
                Storage::disk('public')->delete($category->image_path);
            }
            // Nayi image save
            $validated['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'image_path' => $validated['image_path'] ?? $category->image_path,
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => $category->fresh()
        ]);
    }

    /**
     * 5. Delete category
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();
        $this->clearCache();

        return response()->json([
            'message' => 'Category deleted successfully.'
        ]);
    }

    /**
     * Helper: Clear cached categories
     */
    private function clearCache()
    {
        Cache::increment('categories_cache_version');
    }
}


