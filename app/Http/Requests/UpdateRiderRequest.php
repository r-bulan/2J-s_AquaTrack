<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderRequest extends FormRequest
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
            'status' => 'required|in:Active,Inactive',
            'wage_rate' => 'required|numeric|min:0',
            'cash_advance' => 'nullable|numeric|min:0',
            'area' => 'nullable|string|max:255',
            'vehicle' => 'nullable|string|max:255',
        ];
    }
}
