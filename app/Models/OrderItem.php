<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_id',
        'price',
        'quantity',
    ];

    // রিলেশনশিপ: অর্ডার আইটেম কোন প্রোডাক্টের?
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // রিলেশনশিপ: অর্ডার আইটেম কোন অর্ডারের?
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
