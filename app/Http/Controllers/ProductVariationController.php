<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductVariationController extends Controller
{
    // app/Http/Controllers/ProductVariationController.php

    public function store(Request $request)
    {
        // ১. ভ্যালিডেশন
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variations' => 'required|array',
            'variations.*.price' => 'required|numeric|min:0',
            'variations.*.stock' => 'required|integer|min:0',
            'variations.*.sku'   => 'nullable|string|distinct',
            'variations.*.attribute_values'   => 'required|array',
            'variations.*.attribute_values.*' => 'exists:attribute_values,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($request->product_id);

            // সিকিউরিটি চেক
            if ($product->vendor_id !== auth()->user()->vendor->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $processedVariations = [];

            foreach ($request->variations as $item) {

                // 🚀 STEP 1: SKU জেনারেশন (Daraz Logic)
                $sku = $item['sku'] ?? null;

                // যদি ইউজার SKU না দেয়, আমরা অ্যাট্রিবিউট আইডি দিয়ে ইউনিক SKU বানাবো
                if (empty($sku)) {
                    $attrIds = $item['attribute_values'];
                    sort($attrIds); // আইডিগুলো সাজিয়ে নিব (যাতে 1-4 আর 4-1 একই হয়)

                    // Format: DRZ-V{vendor}-P{product}-{attr1}-{attr2}
                    // Example: DRZ-V1-P10-1-4
                    $skuSuffix = implode('-', $attrIds);
                    $sku = sprintf('DRZ-V%s-P%s-%s', $product->vendor_id, $product->id, $skuSuffix);
                }

                // 🚀 STEP 2: ডুপ্লিকেট চেকিং
                // আমরা চেক করব এই SKU অলরেডি ডাটাবেসে আছে কি না
                $existingVariation = ProductVariation::where('product_id', $product->id)
                    ->where('sku', $sku)
                    ->first();

                if ($existingVariation) {
                    // 🔄 CASE A: যদি থাকে, তাহলে শুধু স্টক এবং প্রাইস আপডেট করব
                    $existingVariation->update([
                        'price' => $item['price'],
                        'stock' => $existingVariation->stock + $item['stock'], // আগের স্টকের সাথে যোগ হবে
                    ]);
                    $variation = $existingVariation;
                } else {
                    // 🆕 CASE B: যদি না থাকে, নতুন তৈরি করব
                    $variation = ProductVariation::create([
                        'product_id' => $product->id,
                        'price'      => $item['price'],
                        'stock'      => $item['stock'],
                        'sku'        => $sku,
                    ]);

                    // পিভট টেবিলে অ্যাট্রিবিউট যুক্ত করা
                    $variation->attributeValues()->sync($item['attribute_values']);
                }

                $processedVariations[] = $variation;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Variations synchronized successfully!',
                'data'    => $processedVariations
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }



    // ১. ভেরিয়েশন আপডেট করা (Price/Stock)
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'numeric|min:0',
            'stock' => 'integer|min:0',
            'sku'   => 'nullable|string|distinct' // ভেন্ডর চাইলে SKU পাল্টাতে পারে
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // ভেরিয়েশন খুঁজে বের করা
            $variation = ProductVariation::findOrFail($id);

            // সিকিউরিটি চেক: এই প্রোডাক্ট কি ভেন্ডরের নিজের?
            // আমরা রিলেশন দিয়ে প্রোডাক্ট > ভেন্ডর আইডি চেক করছি
            if ($variation->product->vendor_id !== auth()->user()->vendor->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // আপডেট করা
            $variation->update($request->only(['price', 'stock', 'sku']));

            return response()->json([
                'success' => true,
                'message' => 'Variation updated successfully!',
                'data' => $variation
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ২. ভেরিয়েশন ডিলিট করা
    public function destroy($id)
    {
        try {
            $variation = ProductVariation::findOrFail($id);

            // সিকিউরিটি চেক
            if ($variation->product->vendor_id !== auth()->user()->vendor->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // ডিলিট করা (পিভট টেবিলের ডাটাও অটোমেটিক মুছে যাবে Cascade এর কারণে)
            $variation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Variation deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
