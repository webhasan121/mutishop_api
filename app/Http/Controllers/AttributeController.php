<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttributeRequest;
use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    // Store Method
    public function store(AttributeRequest $request)
    {
        // এখানে কোনো ভ্যালিডেশন কোড লেখার দরকার নেই!
        // লারাভেল অটোমেটিক AttributeRequest ক্লাস দিয়ে চেক করে নিবে।
        // যদি ভুল থাকে, সে অটোমেটিক JSON Error রিটার্ন করবে।

        $attribute = Attribute::create([
            'vendor_id' => auth()->user()->vendor->id,
            'name'      => $request->name,
            'status'    => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attribute created successfully',
            'data'    => $attribute
        ], 201);
    }

    // Update Method
    public function update(AttributeRequest $request, $id)
    {
        $attribute = Attribute::findOrFail($id);

        // ওনারশিপ চেক
        if ($attribute->vendor_id !== auth()->user()->vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attribute->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Attribute updated successfully',
            'data'    => $attribute
        ]);
    }
}
