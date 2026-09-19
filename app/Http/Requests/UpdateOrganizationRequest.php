<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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

            'members' => ['array', 'max:50'],
            'members.*.name' => ['required_with:members.*.position', 'nullable', 'string', 'max:255'],
            'members.*.position' => ['required_with:members.*.name', 'nullable', 'string', 'max:255'],
            'members.*.contact_number' => ['nullable', 'string', 'max:40'],
            'members.*.email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'org_chart' => 'organizational structure',
            'logo' => 'organization logo',
        ];
    }
}
