<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('contractor_name') && is_string($this->input('contractor_name'))) {
            $name = preg_replace('/\\s+/u', ' ', strip_tags($this->input('contractor_name'))) ?? '';
            $this->merge(['contractor_name' => trim($name)]);
        }
    }

    public function rules(): array
    {
        $contractor = $this->route('contractor');
        $presence = $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];

        return [
            'contractor_name' => [
            ...$presence,
                'string',
                'max:150',
                Rule::unique('contractors', 'contractor_name')->ignore($contractor),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'contractor_name.required' => 'A contractor name is required.',
            'contractor_name.max' => 'The contractor name may not exceed 150 characters.',
            'contractor_name.unique' => 'This contractor is already registered.',
        ];
    }
}
