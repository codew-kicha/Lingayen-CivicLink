<?php

namespace App\Http\Requests;

use App\Models\OrganizationMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->user()->organization;

        return $organization
            ? $this->user()->can('update', $organization)
            : $this->user()->can('create', \App\Models\Organization::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sector' => ['required', Rule::in(config('sectors'))],
            'barangay' => ['required', Rule::in(config('barangays'))],
            'advocacy' => ['nullable', 'string', 'max:2000'],
            'org_chart' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],

            ...OrganizationMember::rules(),
        ];
    }

    public function messages(): array
    {
        return OrganizationMember::messages();
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (is_array($this->input('members')) && $problem = OrganizationMember::representativeProblem($this->input('members'))) {
                $validator->errors()->add('members', $problem);
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'org_chart' => 'organizational structure',
            'logo' => 'organization logo',
        ];
    }
}
