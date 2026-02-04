<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartController extends Controller
{
    // ১. কার্টে প্রোডাক্ট যুক্ত করা
    public function addToCart(Request $request)
    {
        // ক) ভ্যালিডেশন
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'product_variation_id' => 'nullable|exists:product_variations,id',
        ]);

        $user = Auth::user(); // বা Auth::user()
        $sessionId = $request->header('Session-ID'); // ফ্রন্টএন্ড থেকে সেশন আইডি পাঠাতে হবে (গেস্টদের জন্য)

        // খ) কার্ট খুঁজে বের করা (অথবা নতুন বানানো)
        $cart = null;

        if ($user) {
            // লগইন করা ইউজার হলে তার আগের কার্ট খুঁজো
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        } else {
            // গেস্ট ইউজার হলে সেশন আইডি দিয়ে কার্ট খুঁজো
            if (!$sessionId) {
                // যদি সেশন আইডি না থাকে, নতুন একটা জেনারেট করে দাও
                $sessionId = Str::uuid()->toString();
            }
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        }

        // গ) স্টক চেক করা (Stock Check) 📦
        $product = Product::find($request->product_id);
        $stock = $product->stock; // ডিফল্ট স্টক

        // যদি ভেরিয়েশন থাকে (যেমন: Red-XL), তাহলে তার স্টক চেক করো
        if ($request->product_variation_id) {
            $variation = ProductVariation::find($request->product_variation_id);
            $stock = $variation->stock;
        }

        if ($stock < $request->quantity) {
            return response()->json(['message' => 'Out of Stock! Available: ' . $stock], 400);
        }

        // ঘ) আইটেম কার্টে ঢোকানো
        // চেক করি এই আইটেম আগেই কার্টে ছিল কি না
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('product_variation_id', $request->product_variation_id)
            ->first();

        if ($existingItem) {
            // থাকলে কোয়ান্টিটি বাড়িয়ে দাও
            $existingItem->quantity += $request->quantity;
            $existingItem->save();
        } else {
            // না থাকলে নতুন রো বানাও
            CartItem::create([
                'cart_id'              => $cart->id,
                'product_id'           => $request->product_id,
                'product_variation_id' => $request->product_variation_id,
                'quantity'             => $request->quantity
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Added to cart successfully!',
            'session_id' => $sessionId, // গেস্ট ইউজারকে এই আইডি সেভ রাখতে হবে
            'cart_count' => $cart->items()->count()
        ]);
    }

    // ২. কার্ট দেখা (সাথে প্রোডাক্টের ডিটেইলস)
    public function viewCart()
    {
        $userId = auth()->id();

        $cartItems = Cart::where('user_id', $userId)
            ->with(['product' => function ($query) {
                // প্রোডাক্টের শুধু এই তথ্যগুলো আনব (পারফরম্যান্স অপটিমাইজেশন)
                $query->select('id', 'name', 'price', 'file', 'vendor_id');
            }])
            ->get();

        // টোটাল হিসাব করা (Optional: ফ্রন্টএন্ডেও করা যায়, কিন্তু এখান থেকে দেওয়াই ভালো)
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $totalAmount += $item->product->price * $item->quantity;
        }

        return response()->json([
            'success' => true,
            'total_amount' => $totalAmount,
            'data' => $cartItems
        ]);
    }

    // ৩. কার্ট থেকে ডিলিট করা
    public function removeFromCart($id)
    {
        $cartItem = Cart::where('user_id', auth()->id())->where('id', $id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $cartItem->delete();

        return response()->json(['success' => true, 'message' => 'Item removed from cart']);
    }
}
