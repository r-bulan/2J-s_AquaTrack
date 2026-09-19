<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isCustomer();
    }

    public function rules(): array
    {
        return [
            'gallon_type' => 'required|in:Round,Flat',
            'jug_count' => 'required|integer|min:1|max:100',
            'payment_method' => 'required|in:Cash,GCash,Maya,Credit',
            'delivery_address' => 'nullable|string|max:500',
            'preferred_time' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
