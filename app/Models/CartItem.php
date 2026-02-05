<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id', 'product_id', 'product_variation_id', 'quantity'];


    // এই আইটেমটি কোন প্রোডাক্টের?
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    // এই আইটেমটি কোন ভেরিয়েশনের?
    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
