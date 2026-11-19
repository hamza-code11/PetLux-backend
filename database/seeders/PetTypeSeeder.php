<?php

namespace Database\Seeders;

use App\Models\PetType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PetTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Dogs', 'Cats', 'Birds', 'Small Animals', 'Fish'];
        
        foreach ($types as $type) {
            PetType::create([
                'name' => $type,
                'slug' => Str::slug($type),
                'image_path' => 'pet-types/placeholder.jpg',
            ]);
        }
    }
}

