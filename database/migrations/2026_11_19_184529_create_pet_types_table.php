<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique()->index(); // Index for fast lookup
            $table->string('image_path'); // DB me sirf path save hoga
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_types');
    }
};
