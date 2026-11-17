<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class EquipmentRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function rentalRules(bool $updating): array
    {
        $presence = $updating ? ['sometimes', 'required'] : ['required'];
        $amountMaximum = '9999999999.99';

        return [
            'heavy_equipment_id' => [...$presence, 'integer', 'exists:heavy_equipment,id'],
            'contractor_id' => [...$presence, 'integer', 'exists:contractors,id'],
            'project_site_id' => [...$presence, 'integer', 'exists:project_sites,id'],
            'start_date' => [...$presence, 'date'],
            'end_date' => $updating
                ? [...$presence, 'date']
                : [...$presence, 'date', 'after_or_equal:start_date'],
            'fuel_liters_used' => ['sometimes', 'numeric', 'min:0', "max:{$amountMaximum}"],
            'fuel_rate' => [...$presence, 'numeric', 'min:0', "max:{$amountMaximum}"],
        ];
    }

    public function messages(): array
    {
        return [
            'heavy_equipment_id.exists' => 'Select registered heavy equipment.',
            'contractor_id.exists' => 'Select a registered contractor.',
            'project_site_id.exists' => 'Select a registered project site.',
            'end_date.after_or_equal' => 'The rental end date must be on or after the start date.',
            'fuel_rate.required' => 'The fuel rate is required to calculate total rental cost.',
        ];
    }
}
