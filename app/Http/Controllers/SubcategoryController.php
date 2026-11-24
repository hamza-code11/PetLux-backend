<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class SubcategoryController extends Controller
{
    /**
     * 1. List all subcategories with Cache Versioning
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        // Cache versioning
        $version = Cache::get('subcategories_cache_version', 1);
        $cacheKey = "subcategories_v{$version}_page_{$page}_limit_{$perPage}";

        $subcategories = Cache::remember($cacheKey, 86400, function () use ($perPage) {
            return Subcategory::select('id', 'name', 'slug', 'image_path', 'created_at')
                ->latest()
                ->paginate($perPage);
        });

        return response()->json($subcategories);
    }

    /**
     * 2. Store a new subcategory
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subcategories,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:subcategories,slug'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,svg,webp,jfif', 'max:2048'], 
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('subcategories', 'public');
        }

        $subcategory = Subcategory::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'image_path' => $imagePath,
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Subcategory created successfully.',
            'data' => $subcategory
        ], 201);
    }

    /**
     * 3. Show single subcategory
     */
    public function show(Subcategory $subcategory): JsonResponse
    {
        return response()->json([
            'data' => $subcategory
        ]);
    }

    /**
     * 4. Update specific subcategory
     */
    public function update(Request $request, Subcategory $subcategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('subcategories')->ignore($subcategory->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('subcategories')->ignore($subcategory->id)],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,svg,webp,jfif', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // Purani image delete
            if ($subcategory->image_path && Storage::disk('public')->exists($subcategory->image_path)) {
                Storage::disk('public')->delete($subcategory->image_path);
            }
            // Nayi image save
            $validated['image_path'] = $request->file('image')->store('subcategories', 'public');
        }

        $subcategory->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'image_path' => $validated['image_path'] ?? $subcategory->image_path,
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Subcategory updated successfully.',
            'data' => $subcategory->fresh()
        ]);
    }

    /**
     * 5. Delete subcategory
     */
    public function destroy(Subcategory $subcategory): JsonResponse
    {
        if ($subcategory->image_path && Storage::disk('public')->exists($subcategory->image_path)) {
            Storage::disk('public')->delete($subcategory->image_path);
        }

        $subcategory->delete();
        $this->clearCache();

        return response()->json([
            'message' => 'Subcategory deleted successfully.'
        ]);
    }

    /**
     * Helper: Clear cached subcategories
     */
    private function clearCache()
    {
        Cache::increment('subcategories_cache_version');
    }
}
