<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PetType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image_path',
        'show_in_navbar',
    ];

    // Auto append full image URL in API response
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        if (!$this->image_path) {
            return '';
        }
        return Storage::disk('public')->url($this->image_path);
    }
}

