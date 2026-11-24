<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = ['imageable_id', 'imageable_type', 'image_path'];
    protected $appends = ['image_url'];

    public function imageable()
    {
        return $this->morphTo();
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : '';
    }
}

