<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_code',
        'total_amount',
        'payable_amount',
        'shipping_address',
        'payment_method',
        'transaction_id',
        'payment_status',
        'currency',
        'phone',
        'status',
    ];

    // রিলেশনশিপ: অর্ডার কোন ইউজারের?
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // রিলেশনশিপ: অর্ডারের আইটেমসমূহ
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }



}
