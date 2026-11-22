<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Admin create/edit of an organization. On create, a login is optional: imported or walk-in
// organizations can exist without one and get one attached later.
class ManageOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rep_phone' => User::normalizePhone($this->input('rep_phone')),
            'rep_email' => is_string($this->input('rep_email')) ? mb_strtolower(trim($this->input('rep_email'))) : $this->input('rep_email'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'sector' => ['required', Rule::in(config('sectors'))],
            'barangay' => ['required', Rule::in(config('barangays'))],
            'advocacy' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->isMethod('post')) {
            // The representative's fields count only when "Create a login for the representative" is ticked.
            $rules += [
                'create_login' => ['boolean'],
                'rep_last_name' => ['exclude_unless:create_login,1', 'required', 'string', 'max:100', new PersonName],
                'rep_first_name' => ['exclude_unless:create_login,1', 'required', 'string', 'max:100', new PersonName],
                'rep_middle_name' => ['exclude_unless:create_login,1', 'nullable', 'string', 'max:100', new PersonName],
                'rep_suffix' => ['exclude_unless:create_login,1', 'nullable', Rule::in(User::SUFFIXES)],
                'rep_email' => ['exclude_unless:create_login,1', 'required', 'email', 'lowercase', 'max:255', Rule::unique(User::class, 'email')],
                'rep_phone' => ['exclude_unless:create_login,1', 'nullable', 'regex:/^\+639\d{9}$/'],
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['rep_phone.regex' => 'Enter a Philippine mobile number, for example 0917 123 4567.'];
    }

    public function attributes(): array
    {
        return ['rep_last_name' => 'last name', 'rep_first_name' => 'first name', 'rep_middle_name' => 'middle name', 'rep_email' => 'representative email', 'rep_phone' => 'representative mobile number'];
    }
}
