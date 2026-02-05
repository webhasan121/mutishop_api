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
    public function viewCart(Request $request)
    {
        $user = Auth::user();

        $sessionId = $request->header('Session-ID');

        $cart = null;

        if ($user) {
            $cart = Cart::with(['items.product', 'items.variation.attributeValues.attribute'])
                ->where('user_id', $user->id)->first();
        } elseif ($sessionId) {
            $cart = Cart::with(['items.product', 'items.variation.attributeValues.attribute'])
                ->where('session_id', $sessionId)->first();
        }

        if (!$cart) {
            return response()->json(['message' => 'Cart is empty', 'items' => []]);
        }


        $totalAmount = 0;
        foreach ($cart->items as $item) {
            $totalAmount += $item->product->price * $item->quantity;
        }

        return response()->json([
            'success' => true,
            'total_amount' => $totalAmount,
            'data' => $cart->items
        ]);
    }

    // ৩. কার্ট থেকে ডিলিট করা
    // ৩. কার্ট থেকে আইটেম মুছে ফেলা (Remove Item)
    public function removeFromCart(Request $request, $id)
    {
        // $id হলো cart_items টেবিলের প্রাইমারি কি (cart_item_id)

        $user = Auth::user();
        $sessionId = $request->header('Session-ID');

        // ১. আইটেমটি খুঁজে বের করা
        $cartItem = CartItem::find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // ২. সিকিউরিটি চেক: এই আইটেমটি যে কার্টে আছে, সেই কার্ট কি এই ইউজারের? 🕵️‍♂️
        $cart = $cartItem->cart; // রিলেশনশিপ দিয়ে কার্ট ধরলাম

        // লজিক:
        // ক) যদি ইউজার লগইন থাকে -> কার্টের user_id চেক করো
        // খ) যদি ইউজার গেস্ট থাকে -> কার্টের session_id চেক করো

        if ($user) {
            if ($cart->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized access to this cart'], 403);
            }
        } else {
            if ($cart->session_id !== $sessionId) {
                return response()->json(['message' => 'Unauthorized access to this cart'], 403);
            }
        }

        // ৩. ডিলিট করা 🗑️
        $cartItem->delete();

        // ৪. কার্ট যদি খালি হয়ে যায়, মেইন কার্ট ডিলিট করা (অপশনাল, ডাটাবেস ক্লিন রাখার জন্য)
        if ($cart->items()->count() === 0) {
            $cart->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully',
            'cart_count' => $cart->exists ? $cart->items()->count() : 0
        ]);
    }
}
