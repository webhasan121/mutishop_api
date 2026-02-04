<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Brand extends Model
{
    //
    use HasFactory;
    protected $fillable = ['name', 'slug', 'logo', 'status'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($brand) {
            $brand->slug = Str::slug($brand->name);
        });


        // 🚀 ডিলিট লজিক: Brand ডিলিট হলে মেইন logo ডিলিট হবে
        static::deleting(function ($brand) {
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
        });
    }
}
