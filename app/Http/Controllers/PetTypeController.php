<?php

namespace App\Http\Controllers;

use App\Models\PetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class PetTypeController extends Controller
{
    /**
     * 1. List all pet types (GET /api/admin/pet-types)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        // Cache versioning taaki Cache::flush() na karna pade
        $version = Cache::get('pet_types_cache_version', 1);
        $cacheKey = "pet_types_v{$version}_page_{$page}_limit_{$perPage}";

        $petTypes = Cache::remember($cacheKey, 86400, function () use ($perPage) {
            return PetType::select('id', 'name', 'slug', 'image_path', 'created_at')
                ->latest()
                ->paginate($perPage);
        });

        return response()->json($petTypes);
    }

    /**
     * 2. Store a new pet type (POST /api/admin/pet-types)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:pet_types,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:pet_types,slug'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,svg,webp,jfif', 'max:2048'], 
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('pet-types', 'public');
        }

        $petType = PetType::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'image_path' => $imagePath,
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Pet type created successfully.',
            'data' => $petType
        ], 201);
    }

    /**
     * 3. Show single pet type (GET /api/admin/pet-types/{id})
     * YEH METHOD EDIT PAGE KE LIYE ZAROORI HAI
     */
    public function show(PetType $petType): JsonResponse
    {
        return response()->json([
            'data' => $petType
        ]);
    }

    /**
     * 4. Update specific pet type (PUT /api/admin/pet-types/{id})
     */
    public function update(Request $request, PetType $petType): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('pet_types')->ignore($petType->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('pet_types')->ignore($petType->id)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp,jfif', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // Purani image delete karein
            if ($petType->image_path && Storage::disk('public')->exists($petType->image_path)) {
                Storage::disk('public')->delete($petType->image_path);
            }
            // Nayi image save karein
            $validated['image_path'] = $request->file('image')->store('pet-types', 'public');
        }

        $petType->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'image_path' => $validated['image_path'] ?? $petType->image_path,
        ]);

        $this->clearCache();

        return response()->json([
            'message' => 'Pet type updated successfully.',
            'data' => $petType->fresh()
        ]);
    }

    /**
     * 5. Delete pet type (DELETE /api/admin/pet-types/{id})
     */
    public function destroy(PetType $petType): JsonResponse
    {
        if ($petType->image_path && Storage::disk('public')->exists($petType->image_path)) {
            Storage::disk('public')->delete($petType->image_path);
        }

        $petType->delete();
        $this->clearCache();

        return response()->json([
            'message' => 'Pet type deleted successfully.'
        ]);
    }

    /**
     * Helper: Clear cached pet types
     */
    private function clearCache()
    {
        // Cache version ko increment karein. Purana data automatically expire ho jayega.
        Cache::increment('pet_types_cache_version');
    }
}

