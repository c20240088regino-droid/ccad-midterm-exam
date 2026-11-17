<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectSiteRequest extends FormRequest
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
        if ($this->has('project_site') && is_string($this->input('project_site'))) {
            $site = preg_replace('/\\s+/u', ' ', strip_tags($this->input('project_site'))) ?? '';
            $this->merge(['project_site' => trim($site)]);
        }
    }

    public function rules(): array
    {
        $siteId = $this->route('project_site');
        $presence = $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];

        return [
            'project_site' => [
            ...$presence,
                'string',
                'max:200',
                Rule::unique('project_sites', 'project_site')->ignore($siteId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'project_site.required' => 'A project site name is required.',
            'project_site.max' => 'The project site name may not exceed 200 characters.',
            'project_site.unique' => 'This project site is already registered.',
        ];
    }
}
