<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'shipping_options' => ['required', 'array', 'min:1'],
            'shipping_options.*.vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'shipping_options.*.courier_name' => ['required', 'string', 'max:50'],
            'shipping_options.*.service' => ['required', 'string', 'max:50'],
            'shipping_options.*.cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required' => 'Shipping address is required',
            'address_id.exists' => 'Invalid shipping address',
            'shipping_options.required' => 'Shipping method selection is required',
            'shipping_options.*.vendor_id.required' => 'Vendor ID is required for shipping option',
            'shipping_options.*.courier_name.required' => 'Courier name is required',
            'shipping_options.*.service.required' => 'Shipping service is required',
            'shipping_options.*.cost.required' => 'Shipping cost is required',
        ];
    }
}
