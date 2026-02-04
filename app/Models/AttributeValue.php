<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    //
    protected $fillable = ['attribute_id', 'value'];

    // রিলেশন: এই ভ্যালু কোন অ্যাট্রিবিউটের?
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
