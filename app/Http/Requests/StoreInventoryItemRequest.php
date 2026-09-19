<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|in:Gallons,Caps,Seals,Filters,Chemicals,Other',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'reorder_threshold' => 'required|integer|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'last_restocked' => 'nullable|date',
        ];
    }
}
