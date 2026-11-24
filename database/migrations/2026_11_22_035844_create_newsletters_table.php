<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique(); // Ek email sirf ek baar subscribe kar sakta hai
            $table->timestamps(); // created_at (Subscription Date) aur updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
