<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
