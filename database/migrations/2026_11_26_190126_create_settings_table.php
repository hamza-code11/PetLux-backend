<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();  // e.g., 'shipping_charges'
            $table->string('value');          // e.g., '250'
            $table->timestamps();
        });

        // Default shipping charge insert kar dein (e.g., $250)
        DB::table('settings')->insert([
            'key' => 'shipping_charges',
            'value' => '250',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
