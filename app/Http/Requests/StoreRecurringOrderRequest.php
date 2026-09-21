<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isCustomer();
    }

    public function rules(): array
    {
        return [
            'round_count' => 'nullable|integer|min:0|max:100',
            'flat_count' => 'nullable|integer|min:0|max:100',
            'frequency' => 'required|string|in:Weekly,Biweekly,Every 2 weeks,Monthly',
            'next_order_date' => 'required|date|after_or_equal:today',
            'payment_method' => 'required|in:Cash,GCash,Maya,Credit',
            'delivery_address' => 'nullable|string|max:500',
            'preferred_time' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $round = (int) $this->input('round_count', 0);
            $flat = (int) $this->input('flat_count', 0);

            if ($round + $flat < 1) {
                $validator->errors()->add('round_count', 'You must specify at least 1 Round or Flat gallon for your recurring order schedule.');
            }
        });
    }
}
