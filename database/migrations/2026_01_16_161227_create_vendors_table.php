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
        Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

        $table->string('shop_name');
        $table->string('slug')->unique(); // 👈 নতুন: URL এবং ইউনিক চেনার জন্য (যেমন: /shop/my-shop)
        $table->text('address')->nullable();
        $table->text('description')->nullable();
        $table->string('phone')->nullable();
        $table->string('logo')->nullable();
        $table->string('banner')->nullable(); // 👈 নতুন: দোকানের কভার ফটো

        $table->decimal('balance', 10, 2)->default(0); // 👈 নতুন: ভেন্ডরের মোট আয় (Wallet)

        // is_approved এর বদলে status ব্যবহার করা ভালো, এতে Suspended অপশন রাখা যায়
        $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
