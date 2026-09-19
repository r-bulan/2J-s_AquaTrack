<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isCustomer() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:Feedback,Complaint',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|max:2000',
        ];
    }
}
