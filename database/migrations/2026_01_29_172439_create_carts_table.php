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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // ১. কার কার্ট? (লগইন করা থাকলে ID, না থাকলে Null)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // ২. গেস্ট ইউজার হ্যান্ডেল করার জন্য (খুবই জরুরি)
            // যারা লগইন ছাড়া কার্টে মাল ঢোকাবে, তাদের আমরা এই ID দিয়ে চিনব
            $table->string('session_id')->nullable();

            // ৩. ডিসকাউন্ট কুপন (যেমন: EID2026)
            // এটা আমরা আইটেমের সাথে না রেখে মেইন কার্টে রাখি
            $table->string('coupon_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
