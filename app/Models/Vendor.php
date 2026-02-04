<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{

    use HasFactory;
    protected $guarded = []; // সব কলাম পূরণ করা যাবে

    protected $appends = ['logo_url'];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // ফাইলের পূর্ণ URL রিটার্ন করার জন্য অ্যাক্সেসর
    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    // Status Check: ভেন্ডর সাসপেন্ডেড কি না তা চেক করার জন্য
    public function isActive()
    {
        return $this->status === 'approved';
    }
}
