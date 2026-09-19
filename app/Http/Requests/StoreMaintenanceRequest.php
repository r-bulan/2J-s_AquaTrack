<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'equipment_name' => 'required|string|max:255',
            'equipment_type' => 'required|in:RO Membrane,UV Sterilizer,Filter,Pump,Other',
            'install_date' => 'nullable|date',
            'last_service_date' => 'nullable|date',
            'next_service_date' => 'required|date',
            'service_interval_days' => 'required|integer|min:1|max:730',
            'volume_processed' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
