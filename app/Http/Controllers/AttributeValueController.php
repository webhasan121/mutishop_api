<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttributeValueRequest;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
class AttributeValueController extends Controller
{
    // Store Method
    public function store(AttributeValueRequest $request)
    {
        // এখানে কোনো ভ্যালিডেশন কোড লেখার দরকার নেই!
        // লারাভেল অটোমেটিক AttributeRequest ক্লাস দিয়ে চেক করে নিবে।
        // যদি ভুল থাকে, সে অটোমেটিক JSON Error রিটার্ন করবে।

        $attributeValue = AttributeValue::create([
            'attribute_id' => $request->attribute_id,
            'value'        => $request->value,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attribute value created successfully',
            'data'    => $attributeValue
        ], 201);
    }

    // Update Method
    public function update(AttributeValueRequest $request, $id)
    {
        $attributeValue = AttributeValue::findOrFail($id);

        // ওনারশিপ চেক
        if ($attributeValue->attribute->vendor_id !== auth()->user()->vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attributeValue->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Attribute value updated successfully',
            'data'    => $attributeValue
        ]);
    }
}
