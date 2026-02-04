<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Traits\HandlesFileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use HandlesFileUpload;
    //


    public function index(Request $request)
    {




        $query = Product::query()->with('category'); // ক্যাটাগরি রিলেশন সহ লোড হবে
        // 🔍 1. Search Logic (নাম বা ডেসক্রিপশন দিয়ে খোঁজা)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // 📂 2. Category Filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // if ($request->filled('category_id')) {
        //     $catId = $request->category_id;
        //     // আমরা সেই ক্যাটাগরি এবং তার সাব-ক্যাটাগরির সব আইডি বের করব
        //     $categoryIds = Category::where('id', $catId)
        //         ->orWhere('parent_id', $catId)
        //         ->pluck('id');

        //     // where এর বদলে whereIn ব্যবহার করব
        //     $query->whereIn('category_id', $categoryIds);
        // }

        // 💰 3. Price Filter (Min & Max)
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // 4. Sorting (Newest / Price Low to High etc)
        if ($request->filled('sort')) {
            if ($request->sort == 'price_low') {
                $query->orderBy('price', 'asc');
            } elseif ($request->sort == 'price_high') {
                $query->orderBy('price', 'desc');
            } else {
                $query->latest(); // Default Newest
            }
        } else {
            $query->latest();
        }

        $products = $query->get(); // পেজিনেশন চাইলে ->paginate(10) দিতে পারেন

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }


    public function show($id)
    {
        // ১. প্রোডাক্ট খুঁজব এবং সাথে তার সব রিলেশন আনব
        // with() এর ভেতরে আমরা "Dot Notation" ব্যবহার করছি
        $product = Product::with([
            'category',
            'brand',
            'variations.attributeValues.attribute' // Deep Nested Relation ⚓
        ])->findOrFail($id);

        // ২. ফ্রন্টএন্ডের সুবিধার জন্য ডাটা সাজানো (Optional but Recommended) 🛠
        // আমরা অ্যাট্রিবিউটগুলোকে গ্রুপ করব।
        // যাতে ফ্রন্টএন্ডে দেখাতে সুবিধা হয়: "Color: [Red, Blue]", "Size: [S, M]"

        $availableOptions = [];

        foreach ($product->variations as $variation) {
            foreach ($variation->attributeValues as $attrValue) {
                $attrName = $attrValue->attribute->name; // e.g., "Color"
                $value    = $attrValue->value;           // e.g., "Red"

                // ডুপ্লিকেট আটকাতে
                $availableOptions[$attrName][$value] = [
                    'id' => $attrValue->id,
                    'value' => $value
                ];
            }
        }

        // ৩. সুন্দর JSON রিটার্ন করা
        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price, // Default price
                'description' => $product->description,
                'image' => $product->image,
            ],
            // এখানে "UNIQUE" অপশনগুলো পাঠাচ্ছি (বাটন বানানোর জন্য)
            'options' => $availableOptions,

            // আর এখানে "সব ভেরিয়েশন" পাঠাচ্ছি (লজিক মেলানোর জন্য)
            'variations' => $product->variations->map(function($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    // এই ভেরিয়েশনটি কোন কোন অপশনের কম্বিনেশন?
                    'attributes' => $variant->attributeValues->map(function($val) {
                        return [
                            'name' => $val->attribute->name, // Color
                            'value' => $val->value           // Red
                        ];
                    })
                ];
            })
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        // ট্রানজেকশন শুরু (যাতে এরর হলে ডাটাবেসে ভুল ডাটা না ঢোকে)
        DB::beginTransaction();

        try {
            $user = Auth::user();

            // ১. ভেন্ডর চেক (নিরাপত্তার জন্য)
            if (!$user->vendor) {
                return response()->json(['message' => 'You are not a vendor.'], 403);
            }

            $data = $request->validated();

            // ২. মেইন থাম্বনেইল আপলোড
            $data = $this->handleFileUpload($request, $data, null, 'thumbnail', 'public', 'products/thumbnails');

            // ৩. অতিরিক্ত ডাটা সেট করা
            $data['vendor_id'] = $user->vendor->id; // ইউজারের ভেন্ডর আইডি
            $data['status']  = 'active'; // অথবা 'draft'

            // ৪. প্রোডাক্ট তৈরি
            $product = Product::create($data);

            // ৫. গ্যালারি ইমেজ আপলোড (যদি থাকে)
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $path = $this->uploadOne($image, 'products/gallery');
                    // ডাটাবেসে এন্ট্রি দেওয়া
                    ProductImage::create([
                        'product_id' => $product->id,
                        'file'       => $path
                    ]);
                }
            }

            DB::commit(); // সব ঠিক থাকলে সেভ হবে

            return response()->json([
                'success' => true,
                'message' => 'Product uploaded successfully!',
                'data'    => $product->load('images') // ইমেজসহ রেসপন্স
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // এরর হলে সব আনডু হয়ে যাবে
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function update(UpdateProductRequest $request, $id)
    {

        DB::beginTransaction();
        try {
            $product = Product::find($id);

            // ১. প্রোডাক্ট না পেলে এরর
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // ২. অথেনটিফিকেশন চেক (এই ভেন্ডর কি এই প্রোডাক্টের মালিক?)
            if ($product->vendor_id !== auth()->user()->vendor->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }



            $data = $request->validated();


            // 🔥 ৪. থাম্বনেইল আপডেট লজিক
            // যদি রিকোয়েস্টে নতুন 'thumbnail' থাকে, তবে Trait পুরানোটা ডিলিট করে নতুনটা দিবে।
            // ৩য় প্যারামিটারে $product->thumbnail (পুরানো পাথ) পাঠাচ্ছি।
            $data = $this->handleFileUpload($request, $data, $product->thumbnail, 'thumbnail', 'public', 'products/thumbnails');


            $product->update($data);

            if ($request->has('deleted_images')) {
                foreach ($request->deleted_images as $imgId) {

                    $image = ProductImage::find($imgId);

                    // সিকিউরিটি চেক: এই ছবিটা আসলেই এই প্রোডাক্টের কি না?
                    if ($image && $image->product_id == $product->id) {
                        // delete() কল করলেই আপনার Model Event ট্রিগার হবে
                        // এবং ফোল্ডার থেকে ফাইল অটোমেটিক ডিলিট হয়ে যাবে।
                        $image->delete();
                    }
                }
            }



            // 🔥 ৫. গ্যালারি ইমেজ (নতুন ছবি আগেরগুলোর সাথে যুক্ত হবে)
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    // Trait এর uploadOne মেথড ব্যবহার করে
                    $path = $this->uploadOne($image, 'products/gallery');
                    // ডাটাবেসে এন্ট্রি দেওয়া
                    ProductImage::create([
                        'product_id' => $product->id,
                        'file'       => $path
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
                'data'    => $product->refresh()->load('images')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);


        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product and all images deleted successfully']);
    }
}
