<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustCustomerJugsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reason')) {
            $this->merge([
                'reason' => trim((string) $this->input('reason')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'new_balance' => 'required|integer|min:0|max:10000',
            'reason' => 'required|string|min:3|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'new_balance.required' => 'The new jug balance is required.',
            'new_balance.integer' => 'The jug balance must be a whole number.',
            'new_balance.min' => 'Resulting jug balance cannot be negative.',
            'reason.required' => 'A reason explaining the manual adjustment is required.',
            'reason.min' => 'The adjustment reason must be at least 3 characters long.',
        ];
    }
}
