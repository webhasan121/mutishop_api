<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Http\Requests\StoreVendorRequest;
use App\Traits\HandlesFileUpload;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    use HandlesFileUpload;

    // ১. ভেন্ডর হওয়ার রিকোয়েস্ট (Customer -> Request -> Pending Vendor)
    public function becomeVendor(StoreVendorRequest $request)
    {
        $user = auth()->user();

        // ১. চেক: ইউজার কি ইতিমধ্যে আবেদন করেছে?
        if ($user->vendor) {

            if ($user->vendor->status === 'approved') {
                return response()->json(['message' => 'You are already a vendor.'], 400);
            }
            return response()->json(['message' => 'Your application is already pending.'], 400);
        }

        try {

            $data = $request->validated();

            // ব্যানার আপডেট
            // $data = $this->handleFileUpload(
            //     $request,
            //     $data,
            //     $vendor->banner, // 👈 এখানে পুরনো ব্যানারের পাথ দিন
            //     'banner',
            // );

            // ২. লোগো আপলোড (নতুন ক্রিয়েট করছেন তাই oldFile = null)
            // প্যারামিটার: ($request, $data, $oldFile, $field_name, $disk, $folder)
            $data = $this->handleFileUpload($request, $data, null, 'logo');

            // ৩. ব্যানার আপলোড (একইভাবে)
            $data = $this->handleFileUpload($request, $data, null, 'banner');

            $data['slug'] = Str::slug($request->shop_name) . '-' . Str::random(5); // Unique Slug Str::slug($request->shop_name) . '-' . Str::random(5), // Unique Slug
            $data['user_id'] = $user->id;
            $vendor = Vendor::create($data);
            return response()->json([
                'success' => true,
                'message' => 'Vendor application submitted successfully! Please wait for admin approval.',
                'data' => $vendor
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
