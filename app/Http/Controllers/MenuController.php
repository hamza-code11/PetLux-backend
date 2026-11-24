<?php

namespace App\Http\Controllers;

use App\Models\PetType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        $version = Cache::get('products_cache_version', 1);
        $cacheKey = "dynamic_menu_v{$version}";

        $menu = Cache::remember($cacheKey, 3600, function () {
            // 1. Get only Pet Types that are marked to show in navbar
            $petTypes = PetType::select('id', 'name', 'slug')
                ->where('show_in_navbar', true)
                ->get();

            $menuData = [];

            foreach ($petTypes as $pet) {
                // 2. Get categories related to this pet type via products
                $categories = DB::table('products')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->where('products.pet_type_id', $pet->id)
                    ->where('products.status', 'publish')
                    ->select('categories.id', 'categories.name', 'categories.slug')
                    ->distinct()
                    ->get();

                $categoriesData = [];

                foreach ($categories as $cat) {
                    // 3. Get subcategories related to this category & pet type via products
                    $subcategories = DB::table('products')
                        ->join('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
                        ->where('products.pet_type_id', $pet->id)
                        ->where('products.category_id', $cat->id)
                        ->where('products.status', 'publish')
                        ->select('subcategories.id', 'subcategories.name', 'subcategories.slug')
                        ->distinct()
                        ->get();

                    $categoriesData[] = [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'slug' => $cat->slug,
                        'children' => $subcategories->map(function ($sub) {
                            return [
                                'id' => $sub->id,
                                'name' => $sub->name,
                                'slug' => $sub->slug,
                            ];
                        })->toArray(),
                    ];
                }

                // FIX: Always add pet to menu, even if it has no categories
                $menuData[] = [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'slug' => $pet->slug,
                    'categories' => $categoriesData, // Khali ho sakta hai
                ];
            }

            return $menuData;
        });

        return response()->json($menu);
    }
}

