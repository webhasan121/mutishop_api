<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Attribute;

class AttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attribute_id' => [
                'required',
                'integer',
                // চেক করি অ্যাট্রিবিউটটি আসলেই ডাটাবেসে আছে কি না এবং এটি এই ভেন্ডরের কি না
                Rule::exists('attributes', 'id')->where(function ($query) {
                    return $query->where('vendor_id', Auth::user()->vendor->id);
                }),
            ],
            'value' => [
                'required',
                'string',
                'max:50',
                // একই অ্যাট্রিবিউটের ভেতর একই ভ্যালু দুইবার ঢুকবে না (যেমন: Color এর ভেতর দুইবার 'Red')
                Rule::unique('attribute_values', 'value')
                    ->where('attribute_id', $this->attribute_id)
            ]
        ];
    }

    public function messages()
    {
        return [
            'attribute_id.exists' => 'This attribute does not exist or does not belong to you.',
            'value.unique' => 'This value already exists for the selected attribute.'
        ];
    }
}
