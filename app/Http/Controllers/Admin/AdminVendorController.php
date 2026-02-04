<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class AdminVendorController extends Controller
{
    // ১. সব পেন্ডিং রিকোয়েস্ট দেখা
    public function pendingRequests()
    {
        // লেটেস্ট পেন্ডিং রিকোয়েস্ট আগে আসবে
        $requests = Vendor::where('status', 'pending')
                          ->with('user:id,name,email') // ইউজারের নাম ও ইমেইল সহ লোড হবে
                          ->latest()
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    // ২. ভেন্ডর অ্যাপ্রুভ করা
    public function approveVendor($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        //if already approved
        if($vendor->status == 'approved'){
            return response()->json([
                'success' => false,
                'message' => 'Vendor is already approved.',
            ], 400);
        }


        // স্ট্যাটাস আপডেট
        $vendor->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => 'Vendor approved successfully!',
            'data' => $vendor
        ]);
    }

    // ৩. ভেন্ডর রিজেক্ট করা
    public function rejectVendor($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        // রিজেক্ট হলে ডাটা ডিলিট করবেন নাকি স্ট্যাটাস 'suspended' করবেন, তা আপনার ইচ্ছা।
        // আমি এখানে স্ট্যাটাস 'suspended' করছি।
        $vendor->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => 'Vendor application rejected/suspended.',
            'data' => $vendor
        ]);
    }
}
