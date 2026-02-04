<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Traits\HandlesFileUpload;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use HandlesFileUpload;

    public function all_categories()
    {
        $categories = $this->index();
        return $categories;
    }

    // ১. সব ক্যাটাগরি দেখা (Public)
    public function index()
    {
        // শুধুমাত্র মেইন ক্যাটাগরিগুলো আনবে, সাথে তাদের সাব-ক্যাটাগরি থাকবে
        $categories = Category::whereNull('parent_id')
            ->with('subCategories')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    // ২. নতুন ক্যাটাগরি তৈরি (Protected: Admin Only)
    public function store(StoreCategoryRequest $request)
    {

        $data = $this->handleFileUpload($request, $request->validated());
        $data['slug'] = Str::slug($data['name']);
        $category = Category::create($data);
        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }
}
