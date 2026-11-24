<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'name', 'slug', 'regular_price', 'sale_price', 'quantity', 'description'
    ];

    public function product() { return $this->belongsTo(Product::class); }
    
    public function images()
    {
        return $this->morphMany(ProductImage::class, 'imageable');
    }
}

