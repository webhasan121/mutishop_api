<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    //
    use HasFactory;

    protected $fillable = ['product_id', 'file'];
    protected $appends = ['file_url'];
    public function getFileUrlAttribute()
    {
        return $this->file ? asset('storage/' . $this->file) : null;
    }
    // রিলেশন: এই ছবি কোন প্রোডাক্টের?
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 🚀 ডিলিট লজিক: ProductImage ডিলিট হলে ফাইলও ডিলিট হবে
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($productImage) {
            // ৩. গ্যালারির ফাইল ফোল্ডার থেকে ডিলিট
            if ($productImage->file && Storage::disk('public')->exists($productImage->file)) {
                Storage::disk('public')->delete($productImage->file);
            }
        });
    }
}
