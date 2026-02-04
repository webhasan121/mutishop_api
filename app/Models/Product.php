<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $appends = ['thumbnail_url'];

    protected $fillable = [
        'vendor_id',
        'category_id',
        'brand_id',       // ✅ নতুন
        'name',
        'slug',
        'sku',            // ✅ নতুন
        'short_description', // ✅ নতুন
        'description',
        'price',
        'discount_price', // ✅ নতুন
        'discount_start_date', // ✅ নতুন
        'discount_end_date',   // ✅ নতুন
        'stock',
        'stock_status',   // ✅ নতুন
        'product_type',   // ✅ নতুন
        'is_featured',    // ✅ নতুন
        'thumbnail',
        'status'
    ];
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    //category relationship
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    // ফাইলের পূর্ণ URL রিটার্ন করার জন্য অ্যাক্সেসর
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    // ✅ Brand Relation মিসিং ছিল
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    //  এই প্রোডাক্টের সব ভেরিয়েশনগুলো কি? (যেমন: Red-XL, Blue-L)
    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }


    // 🚀 Advanced: অটোমেটিক Slug এবং SKU তৈরি করা
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Slug তৈরি
            $product->slug = Str::slug($product->name) . '-' . Str::random(5);

            // SKU তৈরি (যদি ইউজার না দেয়)
            if (empty($product->sku)) {
                // Example SKU: PRO-RND123-USRID
                $product->sku = 'PRO-' . strtoupper(Str::random(6)) . '-' . $product->vendor_id;
            }
        });

        // 🚀 ডিলিট লজিক: প্রোডাক্ট ডিলিট হলে মেইন থাম্বনেইলও ডিলিট হবে
        static::deleting(function ($product) {
            if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            // ২. গ্যালারি ইমেজগুলো ডিলিট (লুপ চালিয়ে)
            // এটি না করলে গ্যালারির ছবি ফোল্ডারে থেকে যাবে!
            $product->images()->each(function ($image) {
                $image->delete(); // 🔥 এটি ProductImage মডেলের ইভেন্ট ট্রিগার করবে
            });
        });
    }
}
