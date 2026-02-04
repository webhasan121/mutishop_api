<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['vendor_id', 'name', 'status'];

    // রিলেশন: এক অ্যাট্রিবিউটের অনেক ভ্যালু থাকে (Color -> Red, Blue)
    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
