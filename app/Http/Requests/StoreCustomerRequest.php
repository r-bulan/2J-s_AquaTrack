<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'barangay' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'jug_deposit' => 'nullable|numeric|min:0',
            'avg_reorder_days' => 'nullable|integer|min:1|max:365',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
