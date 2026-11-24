<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image_path',
    ];

    /**
     * Append full image URL to API response
     */
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        if (!$this->image_path) {
            return '';
        }
        return Storage::disk('public')->url($this->image_path);
    }
}

