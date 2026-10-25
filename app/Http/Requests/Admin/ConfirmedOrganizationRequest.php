<?php

namespace App\Http\Requests\Admin;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Deleting or merging an organization: the admin re-enters their own password, and a merge names
// the record that stays.
class ConfirmedOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Organization::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'admin_password' => ['required', 'current_password'],
            'into' => $this->routeIs('admin.organizations.merge')
                ? ['required', 'integer', Rule::exists('organizations', 'id'), Rule::notIn([$this->route('organization')->id])]
                : ['exclude'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_password.current_password' => 'That is not your admin password. Use the one you signed in with.',
            'into.required' => 'Choose the organization to keep.',
            'into.not_in' => 'Choose a different organization: a record cannot be merged into itself.',
        ];
    }
}
