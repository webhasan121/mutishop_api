<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class ProductVariationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'variations' => 'required|array', // ভেরিয়েশনগুলো অ্যারে হিসেবে আসবে

            // অ্যারের ভেতরের ডাটা চেক করা
            'variations.*.price' => 'required|numeric|min:0',
            'variations.*.stock' => 'required|integer|min:0',
            'variations.*.sku'   => 'nullable|string|distinct', // SKU ইউনিক হতে হবে

            // অ্যাট্রিবিউট আইডিগুলো (যেমন: Red এর ID, XL এর ID)
            'variations.*.attribute_values'   => 'required|array',
            'variations.*.attribute_values.*' => 'exists:attribute_values,id'
        ];
    }

     protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status'  => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
