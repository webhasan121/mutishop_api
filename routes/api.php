<?php

use App\Http\Controllers\Admin\AdminVendorController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariationController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorOrderController;

Route::group(['prefix' => 'auth'], function ($router) {
    // পাবলিক রাউট
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // প্রোটেক্টেড রাউট (লগইন করা ইউজারদের জন্য)
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/profile', [AuthController::class, 'profile']);
    });
});


// Public Route: সকলের জন্য উন্মুক্ত
Route::get('/all-products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/all-categories', [CategoryController::class, 'all_categories']);

Route::post('/cart/add', [CartController::class, 'addToCart']);
Route::get('/cart', [CartController::class, 'viewCart']);
// Delete Route
Route::delete('/cart/remove/{id}', [CartController::class, 'removeFromCart']);


Route::group(['middleware' => 'auth:api'], function () {
    // ১. সবার জন্য উন্মুক্ত (যেকোনো লগইন ইউজার)
    Route::post('/become-vendor', [VendorController::class, 'becomeVendor']);


    // ২. শুধুমাত্র Admin-দের জন্য
    Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function () {
        Route::apiResource('/categories', CategoryController::class);
        Route::apiResource('/brands', BrandController::class);
        Route::get('/vendor-requests', [AdminVendorController::class, 'pendingRequests']);
        Route::post('/approve-vendor/{id}', [AdminVendorController::class, 'approveVendor']);
        Route::post('/reject-vendor/{id}', [AdminVendorController::class, 'rejectVendor']);
    });

    // ৩. শুধুমাত্র Vendor-দের জন্য (ভবিষ্যতে প্রোডাক্ট আপলোডের জন্য)
    Route::group(['prefix' => 'vendor', 'middleware' => ['role:vendor']], function () {
        Route::apiResource('/products', ProductController::class);
        Route::post('/product-variations', [ProductVariationController::class, 'store']);
        // নতুন রাউট
        Route::put('/product-variations/{id}', [ProductVariationController::class, 'update']);
        Route::delete('/product-variations/{id}', [ProductVariationController::class, 'destroy']);
        // Attributes CRUD (Color, Size)
        Route::apiResource('attributes', AttributeController::class);
        // Attribute Values CRUD (Red, Blue, XL)
        // আলাদা কন্ট্রোলার দিয়ে ভ্যালু ম্যানেজ করা ভালো
        Route::post('/attribute-values', [AttributeValueController::class, 'store']);
        Route::delete('/attribute-values/{id}', [AttributeValueController::class, 'destroy']);
    });

    // ৪. Admin এবং Vendor উভয়ের জন্য (কমন রাউট)
    Route::group(['prefix' => 'common', 'middleware' => ['role:admin,vendor']], function () {
        // Route::get('/dashboard-stats', ...);
    });



    // 🛒 Cart Routes

    // 📦 Order Routes
    Route::post('/place-order', [OrderController::class, 'placeOrder']); // অর্ডার করা
    Route::get('/my-orders', [OrderController::class, 'myOrders']);      // অর্ডার লিস্ট দেখা

    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
    Route::post('/orders/{id}/pay', [OrderController::class, 'retryPayment']);

    // 🏪 Vendor Order Routes
    Route::get('/vendor/orders', [VendorOrderController::class, 'index']);
});



// পেমেন্ট কলব্যাক রাউটস (পাবলিক হতে হবে, Auth Middleware এর বাইরে)
Route::post('/payment/success', [OrderController::class, 'paymentSuccess'])->name('payment.success');
Route::post('/payment/fail', [OrderController::class, 'paymentFail'])->name('payment.fail');
Route::post('/payment/cancel', [OrderController::class, 'paymentCancel'])->name('payment.cancel');
Route::post('/payment/ipn', [OrderController::class, 'paymentIpn'])->name('payment.ipn');
