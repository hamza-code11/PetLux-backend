<?php

namespace Database\Seeders;

use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        $subcategories = ['Dry Food', 'Wet Food', 'Chew Toys', 'Plush Toys'];
        
        foreach ($subcategories as $sub) {
            Subcategory::create([
                'name' => $sub,
                'slug' => Str::slug($sub),
                'image_path' => 'subcategories/placeholder.jpg',
            ]);
        }
    }
}

