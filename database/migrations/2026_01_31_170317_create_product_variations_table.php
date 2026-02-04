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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();

            // কোন প্রোডাক্টের ভেরিয়েশন?
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // ভেরিয়েশনের নিজস্ব ডিটেইলস
            $table->string('sku')->unique()->nullable(); // যেমন: TSHIRT-RED-XL
            $table->string('image')->nullable();         // ভেরিয়েশনের স্পেসিফিক ছবি (যেমন: লাল শার্টের ছবি)

            $table->decimal('price', 10, 2);             // ভেরিয়েশনের দাম (মেইন দাম থেকে ভিন্ন হতে পারে)
            $table->integer('stock')->default(0);        // এই সাইজের কতটি স্টক আছে

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
