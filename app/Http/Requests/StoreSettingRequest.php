<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'round_gallon_price' => 'required|numeric|min:0.01|max:10000',
            'flat_gallon_price' => 'required|numeric|min:0.01|max:10000',
        ];
    }
}
