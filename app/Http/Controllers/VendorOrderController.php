<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;

class VendorOrderController extends Controller
{
    // ১. ভেন্ডর তার সব অর্ডার দেখবে
    public function index()
    {
        $user = auth()->user();

        // সিকিউরিটি চেক
        if (!$user->vendor) {
            return response()->json(['message' => 'Shop not found'], 403);
        }

        // লজিক: order_items টেবিল থেকে শুধু এই ভেন্ডরের আইটেমগুলো আনবে
        // সাথে 'order' রিলেশনশিপ লোড করবে (কাস্টমারের ডিটেইলস দেখার জন্য)
        $orders = OrderItem::where('vendor_id', $user->vendor->id)
            ->with(['product', 'order']) // product = কি বেচল, order = কার কাছে বেচল
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // ২. অর্ডারের স্ট্যাটাস পরিবর্তন করা (যেমন: Pending -> Processing -> Delivered)
    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();

        // শুধু নিজের দোকানের অর্ডার আইটেম আপডেট করতে পারবে
        $orderItem = OrderItem::where('vendor_id', $user->vendor->id)
                              ->where('id', $id)
                              ->first();

        if (!$orderItem) {
            return response()->json(['message' => 'Order item not found'], 404);
        }

        // এখানে আমরা আলাদা একটা কলাম 'delivery_status' রাখতে পারি order_items টেবিলে
        // আপাতত সহজ রাখার জন্য আমরা এটা স্কিপ করছি, কিন্তু ফিউচারে এটা লাগবে।

        return response()->json(['message' => 'Status update logic will be here']);
    }
}
