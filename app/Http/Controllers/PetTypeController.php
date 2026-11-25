<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class PetTypeController extends Controller
{
    /**
     * GET /admin/pet-types
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        $version = Cache::get('pet_types_cache_version', 1);
        $cacheKey = "pet_types_v{$version}_page_{$page}_limit_{$perPage}";

        $petTypes = Cache::remember($cacheKey, 86400, function () use ($perPage) {
            // FIX: 'show_in_navbar' ko select me add karein
            return PetType::select('id', 'name', 'slug', 'image_path', 'show_in_navbar', 'created_at')
                ->latest()
                ->paginate($perPage);
        });

        return response()->json($petTypes);
    }

    /**
     * POST /admin/pet-types
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
            // Image ko 'pet-types' folder me save karega
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
     * GET /admin/pet-types/{id}
     */
    public function show(PetType $petType): JsonResponse
    {
        return response()->json([
            'data' => $petType
        ]);
    }

    /**
     * PUT /admin/pet-types/{id}
     */
    public function update(Request $request, PetType $petType): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('pet_types')->ignore($petType->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('pet_types')->ignore($petType->id)],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,svg,webp,jfif', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // Purani image delete
            if ($petType->image_path && Storage::disk('public')->exists($petType->image_path)) {
                Storage::disk('public')->delete($petType->image_path);
            }
            // Nayi image save
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
     * DELETE /admin/pet-types/{id}
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
     * Cache invalidation
     */
    // private function clearCache(): void
    // {
    //     Cache::increment('pet_types_cache_version');
    // }
    // public function toggleNavbar(PetType $petType): JsonResponse
    // {
    //     // Toggle the value
    //     $petType->show_in_navbar = !$petType->show_in_navbar;
    //     $petType->save();

    //     // Cache clear karein (pet types list ke liye)
    //     Cache::increment('pet_types_cache_version');
        
    //     // Menu cache clear karein (navbar ke liye)
    //     Cache::increment('products_cache_version');

    //     return response()->json([
    //         'message' => 'Pet type navbar visibility updated.',
    //         'show_in_navbar' => $petType->show_in_navbar,
    //     ]);
    // }





        /**
     * Public list for homepage (ALL pet types).
     * GET /pet-types
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $version = Cache::get('pet_types_public_cache_version', 1);
        $cacheKey = "pet_types_public_v{$version}";

        $petTypes = Cache::remember($cacheKey, 86400, function () {
            return PetType::select('id', 'name', 'slug', 'image_path')
                ->orderBy('id')
                ->get()
                ->map(function ($pt) {
                    return [
                        'id'         => $pt->id,
                        'name'       => $pt->name,
                        'slug'       => $pt->slug,
                        'image_path' => $pt->image_path,
                        'image_url'  => $pt->image_path
                            ? asset('storage/' . $pt->image_path)
                            : null,
                    ];
                });
        });

        return response()->json(['data' => $petTypes]);
    }

    

    /**
     * Cache invalidation — invalidates admin + public caches.
     */
    private function clearCache(): void
    {
        Cache::increment('pet_types_cache_version');
        Cache::increment('pet_types_public_cache_version');
    }

    /**
     * PATCH /admin/pet-types/{id}/toggle-navbar
     */
    public function toggleNavbar(PetType $petType): JsonResponse
    {
        $petType->show_in_navbar = ! $petType->show_in_navbar;
        $petType->save();

        $this->clearCache();
        Cache::increment('products_cache_version'); // menu cache

        return response()->json([
            'message'        => 'Pet type navbar visibility updated.',
            'show_in_navbar' => $petType->show_in_navbar,
        ]);
    }


}


