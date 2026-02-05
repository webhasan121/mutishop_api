<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'session_id', 'coupon_code'];

  // কার্টে অনেকগুলো আইটেম থাকে
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}
