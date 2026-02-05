<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // অথেনটিকেশন মিডলওয়্যার রাউটে আছে, তাই এখানে true
    }

    public function rules(): array
    {
        // ভেন্ডর আইডি বের করা (Unique চেক করার জন্য লাগবে)
        $vendorId = Auth::user()->vendor->id;

        // আপডেট করার সময় বর্তমান আইডিকে ইগনোর করতে হবে
        // রাউট থেকে আইডি ধরছি (যদি থাকে)
        $attributeId = $this->route('attribute') ? $this->route('attribute')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                // 🚀 স্পেশাল রুল: একই ভেন্ডর একই নামের অ্যাট্রিবিউট দুইবার বানাতে পারবে না
                // কিন্তু অন্য ভেন্ডর চাইলে বানাতে পারবে।
                Rule::unique('attributes', 'name')
                    ->where(function ($query) use ($vendorId) {
                        return $query->where('vendor_id', $vendorId);
                    })
                    ->ignore($attributeId), // আপডেটের সময় নিজের নামই যেন ডুপ্লিকেট না বলে
            ],
            'status' => 'string' // অপশনাল: যদি স্ট্যাটাস অন/অফ করতে চান
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'You have already created an attribute with this name.',
        ];
    }
}
