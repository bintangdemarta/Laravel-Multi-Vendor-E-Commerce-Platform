<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow both guests and authenticated users
    }

    public function rules(): array
    {
        return [
            'sku_id' => ['required', 'integer', 'exists:skus,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku_id.required' => 'Product variant is required',
            'sku_id.exists' => 'Invalid product variant',
            'quantity.required' => 'Quantity is required',
            'quantity.min' => 'Minimum quantity is 1',
            'quantity.max' => 'Maximum quantity is 999',
        ];
    }
}
