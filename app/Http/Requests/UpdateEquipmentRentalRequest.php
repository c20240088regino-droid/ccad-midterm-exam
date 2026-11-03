<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'heavy_equipment_id' => ['sometimes', 'required', 'integer', 'exists:heavy_equipment,id'],
            'contractor_id' => ['sometimes', 'required', 'integer', 'exists:contractors,id'],
            'project_site_id' => ['sometimes', 'required', 'integer', 'exists:project_sites,id'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
            'fuel_liters_used' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'fuel_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'heavy_equipment_id.exists' => 'Select registered heavy equipment.',
            'contractor_id.exists' => 'Select a registered contractor.',
            'project_site_id.exists' => 'Select a registered project site.',
            'end_date.after_or_equal' => 'The rental end date must be on or after the start date.',
        ];
    }
}
