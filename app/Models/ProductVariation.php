<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariation extends Model
{
    //
    protected $fillable = ['product_id', 'sku', 'image', 'price', 'stock'];
    // রিলেশন: এই ভেরিয়েশন কোন প্রোডাক্টের?
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    // ২. এই ভেরিয়েশনে কি কি অপশন আছে? (যেমন: Red, XL)
    // আমরা সরাসরি 'attribute_values' টেবিলে এক্সেস করব Pivot টেবিলের মাধ্যমে
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variation_combinations', 'product_variation_id', 'attribute_value_id');
    }



}
