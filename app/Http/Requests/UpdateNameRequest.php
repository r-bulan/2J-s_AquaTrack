<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Your display name is required.',
            'name.min' => 'Display name must be at least 2 characters long.',
            'name.max' => 'Display name cannot exceed 100 characters.',
        ];
    }
}
