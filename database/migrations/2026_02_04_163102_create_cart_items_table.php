<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            // ১. এটা কোন ঝুড়ির মাল?
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');

            // ২. কোন প্রোডাক্ট? (মেইন প্রোডাক্ট আইডি)
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // ৩. কোন ভেরিয়েশন? (যেমন: Red-XL)
            // এটা Nullable হতে হবে, কারণ সব প্রোডাক্টের ভেরিয়েশন থাকে না
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->onDelete('set null');

            // ৪. কয়টা নিবে? (Quantity)
            $table->integer('quantity')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
