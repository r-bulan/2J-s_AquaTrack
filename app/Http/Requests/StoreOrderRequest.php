<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'type' => 'required|in:Walk-in,Phone,Online,Recurring',
            'gallon_type' => 'required|in:Round,Flat',
            'jug_count' => 'required|integer|min:1',
            'payment_method' => 'required|in:Cash,GCash,Maya,Credit',
            'delivery_address' => 'nullable|string|max:500',
            'preferred_time' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'recurring' => 'nullable|boolean',
        ];
    }
}
