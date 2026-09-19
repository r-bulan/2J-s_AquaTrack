<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProofOfDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isRider() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'proof_photo' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB max
            'signature' => 'nullable|string', // Base64 data URL
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
