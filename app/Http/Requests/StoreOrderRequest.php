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
            'round_count' => 'nullable|integer|min:0|max:500',
            'flat_count' => 'nullable|integer|min:0|max:500',
            'gallon_type' => 'nullable|in:Round,Flat,Mixed',
            'jug_count' => 'nullable|integer|min:0|max:500',
            'payment_method' => 'required|in:Cash,GCash,Maya,Credit',
            'delivery_address' => 'nullable|string|max:500',
            'preferred_time' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'recurring' => 'nullable|boolean',
            'recurring_order_id' => 'nullable|exists:recurring_orders,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $round = (int) $this->input('round_count', 0);
            $flat = (int) $this->input('flat_count', 0);
            $legacy = (int) $this->input('jug_count', 0);

            if (($round + $flat < 1) && $legacy < 1) {
                $validator->errors()->add('round_count', 'Order must contain at least 1 Round or Flat gallon.');
            }
        });
    }
}
