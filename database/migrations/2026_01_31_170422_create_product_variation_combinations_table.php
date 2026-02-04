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
        Schema::create('product_variation_combinations', function (Blueprint $table) {
            $table->id();
            // কোন ভেরিয়েশন? (যেমন: Variation ID 1)
            $table->foreignId('product_variation_id')->constrained('product_variations')->onDelete('cascade');
            // কোন ভ্যালু? (যেমন: Red অথবা XL)
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variation_combinations');
    }
};
