<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        // ১. ভ্যালিডেশন
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'required|string',
            'phone' => 'required|string',
            'payment_method' => 'required|in:cod,sslcommerz',
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = auth()->user();

        // ২. ইউজারের কার্ট ডাটা নিয়ে আসা
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // ৩. টোটাল এমাউন্ট হিসাব করা
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            // চেক: অর্ডার করার ঠিক আগ মুহূর্তে স্টক আছে তো?
            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'message' => "Stock out for product: {$item->product->name}. Please update cart."
                ], 400);
            }
            $totalAmount += $item->product->price * $item->quantity;
        }

        $deliveryCharge = 60;
        $grandTotal = $totalAmount + $deliveryCharge;
        $tran_id = "TRX-" . Str::random(10);

        // 🔥 ৪. ট্রানজ্যাকশন শুরু (সব হবে, নাহলে কিছুই হবে না)
        DB::beginTransaction();

        try {
            // A. মেইন অর্ডার তৈরি
            $order = Order::create([
                'user_id' => $user->id,
                'invoice_code' => 'ORD-' . strtoupper(Str::random(8)), // যেমন: ORD-AB12XY99
                'total_amount' => $grandTotal,
                'payable_amount' => $grandTotal, // ডিসকাউন্ট থাকলে পরে বিয়োগ হবে
                'shipping_address' => $request->shipping_address,
                'phone' => $request->phone,
                'payment_method' => $request->payment_method,
                'payment_status' => 'unpaid',
                'transaction_id' => $tran_id,
                'currency' => 'BDT',
                'status' => 'pending'
            ]);

            // B. অর্ডার আইটেম তৈরি এবং স্টক কমানো
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'vendor_id' => $item->product->vendor_id, // প্রোডাক্টটি যে দোকানের
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                ]);

                // 📉 স্টক কমানো
                $item->product->decrement('stock', $item->quantity);
            }

            // C. কার্ট খালি করে দেওয়া
            Cart::where('user_id', $user->id)->delete();

            DB::commit();


            if ($request->payment_method === 'sslcommerz') {
                // পেমেন্ট লিংক জেনারেট
                $paymentUrl = $this->initiateSslCommerz($order, $user);
                if ($paymentUrl) {
                    return response()->json([
                        'status' => 'success',
                        'payment_needed' => true,
                        'payment_url' => $paymentUrl,
                        'message' => 'Redirecting to payment gateway...'
                    ]);
                } else {
                    // যদি SSLCommerz কানেক্ট না হয়
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Could not initiate payment gateway.'
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully!',
                    'order_id' => $order->id,
                    'invoice' => $order->invoice_code
                ], 201);
            }

            // সব ঠিক থাকলে ডাটাবেসে সেভ হবে
        } catch (\Exception $e) {
            // কোনো ভুল হলে সব আগের অবস্থায় ফিরে যাবে
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Order failed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function initiateSslCommerz($order, $user)
    {
        $post_data = [];
        $post_data['store_id'] = env('SSLCOMMERZ_STORE_ID');
        $post_data['store_passwd'] = env('SSLCOMMERZ_STORE_PASSWORD');
        $post_data['total_amount'] = $order->total_amount;
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = $order->transaction_id;

        // Success/Fail URL (এগুলো আমরা api.php তে বানাবো)
        $post_data['success_url'] = route('payment.success');
        $post_data['fail_url'] = route('payment.fail');
        $post_data['cancel_url'] = route('payment.cancel');
        $post_data['ipn_url'] = route('payment.ipn');

        // Customer Info
        $post_data['cus_name'] = $user->name;
        $post_data['cus_email'] = $user->email ?? 'guest@email.com';
        $post_data['cus_add1'] = $order->shipping_address;
        $post_data['cus_phone'] = $order->phone;
        $post_data['cus_city'] = "Dhaka";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['shipping_method'] = "sslcommerz";
        $post_data['ship_name'] = "" . $user->name;
        $post_data['ship_add1'] = $order->shipping_address;
        $post_data['ship_city'] = "Dhaka";
        $post_data['ship_country'] = "Bangladesh";
        $post_data['product_name'] = "Order #" . $order->invoice_code;
        $post_data['ship_postcode'] = "1207";
        $post_data['product_category'] = "Ecommerce";
        $post_data['product_profile'] = "general";




        // API Call to SSLCommerz
        $direct_api_url = env('SSLCOMMERZ_IS_SANDBOX')
            ? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php"
            : "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); # KEEP IT FALSE FOR SANDBOX

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);


        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $sslcommerzResponse = json_decode($content, true);

            if (isset($sslcommerzResponse['GatewayPageURL'])) {
                return $sslcommerzResponse['GatewayPageURL'];
            } else {
                return null; // Error handling needed
            }
        } else {
            curl_close($handle);
            return null;
        }
    }

    // পেমেন্ট সফল হলে এখানে আসবে
    public function paymentSuccess(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        $order = Order::where('transaction_id', $tran_id)->first();

        if ($order && $order->payment_status == 'unpaid') {
            // এখানে চাইলে আরও ভ্যালিডেশন API কল করা যায় (SSLCommerz Order Validate API)
            // অ্যাডভান্সড কাজের জন্য 'VALID' বা 'VALIDATED' চেক করা উচিত

            $order->payment_status = 'paid';
            $order->status = 'processing';
            $order->save();
        }

        // অ্যাপের জন্য একটা সিম্পল HTML মেসেজ রিটার্ন করা
        // অ্যাপ যখন WebView দেখবে, এই পেজটি লোড হলে সে বুঝবে পেমেন্ট সফল
        return "
            <html>
            <head><title>Payment Success</title></head>
            <body style='text-align:center; padding:50px;'>
                <h1 style='color:green;'>Payment Successful! 🎉</h1>
                <p>Please close this window to verify order.</p>
                <script>
                   // অ্যাপকে সংকেত দেওয়ার জন্য (যদি React Native WebView এর postMessage ইউজ করেন)
                   // window.ReactNativeWebView.postMessage('PAYMENT_SUCCESS');
                </script>
            </body>
            </html>
        ";
    }

    public function paymentFail(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $order = Order::where('transaction_id', $tran_id)->first();

        if ($order) {
            $order->payment_status = 'Failed';
            $order->status = 'Cancelled';
            $order->save();
        }

        return "<h1 style='color:red; text-align:center;'>Payment Failed! ❌</h1>";
    }

    public function paymentCancel(Request $request)
    {
        return "<h1 style='color:orange; text-align:center;'>Payment Cancelled! ⚠️</h1>";
    }


    // ইউজারের নিজের অর্ডার দেখার ফাংশন
    public function myOrders()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('items.product') // রিলেশনশিপ লোড
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }
    // ১. অর্ডার ক্যান্সেল করা
    public function cancelOrder($id)
    {
        $user = auth()->user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can be cancelled'], 400);
        }

        // অর্ডার স্ট্যাটাস আপডেট
        $order->status = 'cancelled';

        // চাইলে এখানে স্টক ব্যাক করার লজিক বসাতে পারেন (Optional)
        foreach($order->items as $item) {
             $item->product->increment('stock', $item->quantity);
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order has been cancelled successfully.'
        ]);
    }

    // ২. পুনরায় পেমেন্ট করা (Pay Now)
    public function retryPayment($id)
    {
        $user = auth()->user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order is already paid'], 400);
        }

        if ($order->payment_method !== 'sslcommerz') {
            return response()->json(['message' => 'Pay Now is only available for Online Payment'], 400);
        }

        // নতুন ট্রানজেকশন আইডি জেনারেট করা ভালো (সেফটির জন্য)
        $order->transaction_id = "TRX-" . Str::random(10);
        $order->save();

        // পেমেন্ট লিংক জেনারেট
        $paymentUrl = $this->initiateSslCommerz($order, $user);

        if ($paymentUrl) {
            return response()->json([
                'success' => true,
                'payment_url' => $paymentUrl
            ]);
        } else {
            return response()->json(['message' => 'Could not initiate payment'], 500);
        }
    }
}
