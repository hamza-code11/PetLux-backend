<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'status', 'pet_type_id', 'category_id', 'subcategory_id',
        'short_description', 'long_description', 'has_variants', 'regular_price',
        'sale_price', 'quantity'
    ];

    protected $casts = [
        'has_variants' => 'boolean',
    ];

    // Relationships
    public function petType() { return $this->belongsTo(PetType::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function subcategory() { return $this->belongsTo(Subcategory::class); }

    // Variants Relationship
    public function variants() { return $this->hasMany(ProductVariant::class); }
    
    // Images Relationship (Hum ek alag table banayenge images ke liye, ya direct JSON column bhi use kar sakte hain. Yahan Polymorphic relation use kar rahe hain product aur variant dono ke liye)
    public function images()
    {
        return $this->morphMany(ProductImage::class, 'imageable');
    }





    // Product.php me add karein:
public function reviews()
{
    return $this->hasMany(Review::class);
}

// Approved reviews ke liye shortcut
public function approvedReviews()
{
    return $this->hasMany(Review::class)->where('status', 'approved');
}




}

