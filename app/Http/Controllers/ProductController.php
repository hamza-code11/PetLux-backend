<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        $version = Cache::get('products_cache_version', 1);
        $cacheKey = "products_v{$version}_page_{$page}_limit_{$perPage}";

        $products = Cache::remember($cacheKey, 86400, function () use ($perPage) {
            return Product::with(['petType:id,name', 'category:id,name', 'subcategory:id,name', 'images', 'variants'])
                ->latest()
                ->paginate($perPage);
        });

        return response()->json($products);
    }

    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'status' => 'required|in:draft,publish',
            'pet_type_id' => 'nullable|exists:pet_types,id',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'has_variants' => 'required|in:true,false,1,0',
            
            // Simple Product Validation
            'regular_price' => 'required_if:has_variants,false,0|nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'quantity' => 'required_if:has_variants,false,0|nullable|integer|min:0',
            'main_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            
            // Variant Product Validation
            'variants' => 'required_if:has_variants,true,1|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.slug' => 'required|string|max:255',
            'variants.*.regular_price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'required|integer|min:0',
            'variants.*.description' => 'nullable|string',
            'variants.*.images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // <-- YEH ADD KAREIN
        ]);

        try {
            DB::beginTransaction();

            $isVariant = filter_var($validated['has_variants'], FILTER_VALIDATE_BOOLEAN);

            $product = Product::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'status' => $validated['status'],
                'pet_type_id' => $validated['pet_type_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'long_description' => $validated['long_description'] ?? null,
                'has_variants' => $isVariant,
                'regular_price' => $isVariant ? null : $validated['regular_price'],
                'sale_price' => $isVariant ? null : ($validated['sale_price'] ?? null),
                'quantity' => $isVariant ? 0 : $validated['quantity'],
            ]);

            // Handle Simple Product Images
            if (!$isVariant && $request->hasFile('main_images')) {
                foreach ($request->file('main_images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create(['image_path' => $path]);
                }
            }

            // Handle Variants
            if ($isVariant && !empty($request->variants)) {
                $variantsData = $request->variants;
                
                foreach ($variantsData as $index => $variantData) {
                    $variant = $product->variants()->create([
                        'name' => $variantData['name'],
                        'slug' => $variantData['slug'],
                        'regular_price' => $variantData['regular_price'],
                        'sale_price' => $variantData['sale_price'] ?? null,
                        'quantity' => $variantData['quantity'],
                        'description' => $variantData['description'] ?? null,
                    ]);

                    // FIX: Handle variant images properly
                    if ($request->hasFile("variants.{$index}.images")) {
                        $files = $request->file("variants.{$index}.images");
                        // Agar single file hai toh array me convert karein
                        if (!is_array($files)) {
                            $files = [$files];
                        }
                        
                        foreach ($files as $file) {
                            $path = $file->store('products/variants', 'public');
                            $variant->images()->create(['image_path' => $path]);
                        }
                    }
                }
            }

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Product created successfully.',
                'data' => $product->load('images', 'variants.images')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create product.', 'error' => $e->getMessage()], 500);
        }
    }

    

    public function update(Request $request, Product $product): JsonResponse
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:products,slug,' . $product->id,
        'status' => 'required|in:draft,publish',
        'pet_type_id' => 'nullable|exists:pet_types,id',
        'category_id' => 'nullable|exists:categories,id',
        'subcategory_id' => 'nullable|exists:subcategories,id',
        'short_description' => 'nullable|string',
        'long_description' => 'nullable|string',
        'has_variants' => 'required|in:true,false,1,0',
        'regular_price' => 'required_if:has_variants,false,0|nullable|numeric|min:0',
        'sale_price' => 'nullable|numeric|min:0',
        'quantity' => 'required_if:has_variants,false,0|nullable|integer|min:0',
        'main_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'deleted_image_ids' => 'nullable|array',
        'deleted_image_ids.*' => 'integer',
        'variants' => 'required_if:has_variants,true,1|array',
        'variants.*.id' => 'nullable|integer',
        'variants.*.name' => 'required|string|max:255',
        'variants.*.slug' => 'required|string|max:255',
        'variants.*.regular_price' => 'required|numeric|min:0',
        'variants.*.sale_price' => 'nullable|numeric|min:0',
        'variants.*.quantity' => 'required|integer|min:0',
        'variants.*.description' => 'nullable|string',
        'variants.*.images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'variants.*.deleted_image_ids' => 'nullable|array',
        'variants.*.deleted_image_ids.*' => 'integer',
    ]);

    $filesToDelete = []; // commit ke baad delete honge

    try {
        DB::beginTransaction();

        $isVariant = filter_var($validated['has_variants'], FILTER_VALIDATE_BOOLEAN);

        // 1. Product ki apni images jo user ne delete ki hain
        if (!empty($validated['deleted_image_ids'])) {
            $variantIds = $product->variants()->pluck('id');

            $imagesToDelete = ProductImage::whereIn('id', $validated['deleted_image_ids'])
                ->where(function ($q) use ($product, $variantIds) {
                    $q->where(function ($q2) use ($product) {
                        $q2->where('imageable_type', Product::class)
                           ->where('imageable_id', $product->id);
                    })->orWhere(function ($q2) use ($variantIds) {
                        $q2->where('imageable_type', ProductVariant::class)
                           ->whereIn('imageable_id', $variantIds);
                    });
                })->get();

            foreach ($imagesToDelete as $img) {
                $filesToDelete[] = $img->image_path;
                $img->delete();
            }
        }

        // 2. Product details update
        $product->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'status' => $validated['status'],
            'pet_type_id' => $validated['pet_type_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'has_variants' => $isVariant,
            'regular_price' => $isVariant ? null : $validated['regular_price'],
            'sale_price' => $isVariant ? null : ($validated['sale_price'] ?? null),
            'quantity' => $isVariant ? 0 : $validated['quantity'],
        ]);

        // 3. Simple product ki nayi images
        if (!$isVariant && $request->hasFile('main_images')) {
            foreach ($request->file('main_images') as $file) {
                $path = $file->store('products', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }

        // 4. Variants SYNC (delete-all nahi)
        $existingVariants = $product->variants()->with('images')->get();

        if ($isVariant) {
            $keptIds = [];

            foreach (($request->variants ?? []) as $index => $variantData) {
                $variant = null;

                // Pehle id se match
                if (!empty($variantData['id'])) {
                    $variant = $existingVariants->firstWhere('id', (int) $variantData['id']);
                }
                // Fallback: slug se match (agar frontend id nahi bhej raha)
                if (!$variant) {
                    $variant = $existingVariants->firstWhere('slug', $variantData['slug']);
                }
                // Ek variant do baar match na ho
                if ($variant && in_array($variant->id, $keptIds)) {
                    $variant = null;
                }

                $attrs = [
                    'name' => $variantData['name'],
                    'slug' => $variantData['slug'],
                    'regular_price' => $variantData['regular_price'],
                    'sale_price' => $variantData['sale_price'] ?? null,
                    'quantity' => $variantData['quantity'],
                    'description' => $variantData['description'] ?? null,
                ];

                if ($variant) {
                    $variant->update($attrs);
                } else {
                    $variant = $product->variants()->create($attrs);
                    $variant->setRelation('images', collect());
                }

                $keptIds[] = $variant->id;

                // Is variant ki selected images delete
                if (!empty($variantData['deleted_image_ids'])) {
                    $variantImgsToDelete = $variant->images->whereIn('id', $variantData['deleted_image_ids']);
                    foreach ($variantImgsToDelete as $img) {
                        $filesToDelete[] = $img->image_path;
                        $img->delete();
                    }
                }

                // Nayi images ADD (purani delete nahi hoti)
                if ($request->hasFile("variants.{$index}.images")) {
                    $files = $request->file("variants.{$index}.images");
                    if (!is_array($files)) {
                        $files = [$files];
                    }
                    foreach ($files as $file) {
                        $path = $file->store('products/variants', 'public');
                        $variant->images()->create(['image_path' => $path]);
                    }
                }
            }

            // Jo variants form me nahi aaye wo delete
            foreach ($existingVariants->whereNotIn('id', $keptIds) as $oldVariant) {
                foreach ($oldVariant->images as $img) {
                    $filesToDelete[] = $img->image_path;
                }
                $oldVariant->images()->delete();
                $oldVariant->delete();
            }
        } else {
            // Product simple ban gaya: saare variants hata do
            foreach ($existingVariants as $oldVariant) {
                foreach ($oldVariant->images as $img) {
                    $filesToDelete[] = $img->image_path;
                }
                $oldVariant->images()->delete();
                $oldVariant->delete();
            }
        }

        DB::commit();

        // Files commit ke baad delete
        if (!empty($filesToDelete)) {
            Storage::disk('public')->delete($filesToDelete);
        }

        $this->clearCache();

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $product->load('images', 'variants.images')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to update product.', 'error' => $e->getMessage()], 500);
    }
}

public function destroy(Product $product): JsonResponse
{
    $filesToDelete = [];

    try {
        DB::beginTransaction();

        foreach ($product->variants as $variant) {
            foreach ($variant->images as $vImg) {
                $filesToDelete[] = $vImg->image_path;
            }
            $variant->images()->delete(); // morph rows cascade nahi hoti, isliye manually
        }

        foreach ($product->images as $img) {
            $filesToDelete[] = $img->image_path;
        }
        $product->images()->delete();

        $product->delete(); // variants FK cascade se delete

        DB::commit();

        if (!empty($filesToDelete)) {
            Storage::disk('public')->delete($filesToDelete);
        }

        $this->clearCache();

        return response()->json(['message' => 'Product deleted successfully.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to delete product.'], 500);
    }
}





    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'data' => $product->load('petType', 'category', 'subcategory', 'images', 'variants.images')
        ]);
    }

    


    private function clearCache()
    {
        Cache::increment('products_cache_version');
    }








    public function publicIndex(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 16);
        
        $query = Product::with([
            'petType:id,name,slug', 
            'category:id,name,slug', 
            'subcategory:id,name,slug', 
            'images',
            'variants.images'
        ])->where('status', 'publish');

        // --- Slug Based Filtering ---
        
        // 1. Pet Type Slug
        if ($request->has('pet_type_slug') && !empty($request->pet_type_slug)) {
            $query->whereHas('petType', function($q) use ($request) {
                $q->where('slug', $request->pet_type_slug);
            });
        }

        // 2. Category Slug
        if ($request->has('category_slug') && !empty($request->category_slug)) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        // 3. Subcategory Slug
        if ($request->has('subcategory_slug') && !empty($request->subcategory_slug)) {
            $query->whereHas('subcategory', function($q) use ($request) {
                $q->where('slug', $request->subcategory_slug);
            });
        }

        // 4. Search
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // 5. Price Range (Simple products only for now)
        if ($request->has('min_price') && $request->has('max_price')) {
            $min = $request->min_price;
            $max = $request->max_price;
            $query->where(function($q) use ($min, $max) {
                $q->where(function($q2) use ($min, $max) {
                    $q2->where('has_variants', false)
                    ->where(function($q3) use ($min, $max) {
                        $q3->where('sale_price', '>=', $min)->where('sale_price', '<=', $max)
                            ->orWhere(function($q4) use ($min, $max) {
                                $q4->whereNull('sale_price')
                                    ->where('regular_price', '>=', $min)
                                    ->where('regular_price', '<=', $max);
                            });
                    });
                });
            });
        }

        $products = $query->latest()->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Get Sidebar Filters Data based on current URL slugs
     */
    public function getFilters(Request $request): JsonResponse
    {
        // Base query: sirf published products (NO SLUG FILTERING - always return all data)
        $baseQuery = Product::where('status', 'publish');

        // Get unique Pet Types from ALL products
        $petTypes = (clone $baseQuery)->with('petType:id,name,slug')
            ->get()
            ->pluck('petType')
            ->unique('id')
            ->values();

        // Get unique Categories from ALL products
        $categories = (clone $baseQuery)->with('category:id,name,slug')
            ->get()
            ->pluck('category')
            ->unique('id')
            ->values();

        // Get unique Subcategories from ALL products
        $subcategories = (clone $baseQuery)->with('subcategory:id,name,slug')
            ->get()
            ->pluck('subcategory')
            ->filter()
            ->unique('id')
            ->values();

        // Map categories with their respective subcategories
        $categoriesWithSubs = $categories->map(function($cat) use ($baseQuery) {
            // Get subcategories specific to THIS category from all products
            $catSubs = (clone $baseQuery)->whereHas('category', function($q) use ($cat) {
                $q->where('id', $cat->id);
            })->with('subcategory:id,name,slug')
            ->get()
            ->pluck('subcategory')
            ->filter()
            ->unique('id')
            ->values();

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'children' => $catSubs,
            ];
        });

        return response()->json([
            'pet_types' => $petTypes,
            'categories' => $categoriesWithSubs,
            'subcategories' => $subcategories,
        ]);
    }






    // app/Http/Controllers/ProductController.php me add karein

    public function publicShow(string $slug): JsonResponse
    {
        $product = Product::with([
            'petType:id,name,slug',
            'category:id,name,slug',
            'subcategory:id,name,slug',
            'images',
            'variants.images'
        ])->where('slug', $slug)->where('status', 'publish')->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json(['data' => $product]);
    }





    


}
