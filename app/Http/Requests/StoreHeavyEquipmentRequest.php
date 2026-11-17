<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeavyEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'equipment_tag' => ['required', 'string', 'max:50', 'unique:heavy_equipment,equipment_tag'],
            'machine_model' => ['required', 'string', 'max:150'],
            'daily_rate' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'machine_status' => ['sometimes', 'in:Available,Maintenance'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('equipment_tag') && is_string($this->input('equipment_tag'))) {
            $this->merge(['equipment_tag' => mb_strtoupper(trim($this->input('equipment_tag')))]);
        }

        if ($this->has('machine_model') && is_string($this->input('machine_model'))) {
            $model = preg_replace('/\\s+/u', ' ', strip_tags($this->input('machine_model'))) ?? '';
            $this->merge(['machine_model' => trim($model)]);
        }
    }

    public function messages(): array
    {
        return [
            'equipment_tag.required' => 'An equipment tag is required.',
            'equipment_tag.unique' => 'This equipment tag is already registered.',
            'machine_model.required' => 'A machine model is required.',
            'daily_rate.numeric' => 'The daily rate must be a valid amount.',
            'machine_status.in' => 'Equipment status must be Available or Maintenance; rental status is managed automatically.',
        ];
    }
}
