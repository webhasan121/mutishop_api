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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // প্রোডাক্ট কোন দোকানের এবং কোন ক্যাটাগরির
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            // 2. Basic Info
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable(); // 👈 Advanced: ইউনিক প্রোডাক্ট কোড
            $table->text('short_description')->nullable(); // ছোট বিবরণ
            $table->longText('description')->nullable();   // বিস্তারিত বিবরণ (HTML Editor এর জন্য longText ভালো)

            // 3. Pricing & Discount (Advanced)
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable(); // অফার প্রাইস
            $table->date('discount_start_date')->nullable(); // অফার শুরুর তারিখ
            $table->date('discount_end_date')->nullable();   // অফার শেষের তারিখ

            $table->integer('stock')->default(0); // স্টক
            $table->enum('stock_status', ['in_stock', 'out_of_stock'])->default('in_stock');
            $table->enum('product_type', ['simple', 'variable'])->default('simple'); // ফিউচারের জন্য

            // 5. Status
            $table->boolean('is_featured')->default(false); // হোমপেইজে দেখানোর জন্য
            $table->enum('status', ['active', 'inactive', 'draft'])->default('draft');

            $table->string('thumbnail')->nullable(); // ইমেজের জন্য 'thumbnail' কলাম
            $table->timestamps();
            $table->softDeletes(); // 👈 ডাটা সেফটির জন্য
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
