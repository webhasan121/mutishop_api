<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $appends = ['file_url'];


    protected $fillable = ['name', 'slug', 'file', 'parent_id', 'status'];

    // সাব-ক্যাটাগরি বের করার জন্য রিলেশনশিপ
    public function subCategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // প্যারেন্ট ক্যাটাগরি বের করার জন্য
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // ফাইলের পূর্ণ URL রিটার্ন করার জন্য অ্যাক্সেসর
    public function getFileUrlAttribute()
    {
        return $this->file ? asset('storage/' . $this->file) : null;
    }
}
