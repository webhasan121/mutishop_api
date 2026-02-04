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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('invoice_code')->unique(); // যেমন: ORD-2024001
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('payable_amount', 10, 2);

            $table->text('shipping_address');
            $table->string('phone');

            // পেমেন্ট এবং অর্ডার স্ট্যাটাস
            $table->enum('payment_status', ['paid', 'unpaid', 'cod'])->default('unpaid');
            $table->string('payment_method')->default('cod'); // cod, sslcommerz
            $table->string('transaction_id')->nullable(); // SSLCommerz Tran ID
            $table->string('currency')->default('BDT');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
