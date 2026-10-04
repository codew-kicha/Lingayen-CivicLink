<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Admin create/edit of an organization. On create, the representative is optional: imported or
// walk-in organizations can exist without a login and get one attached later.
class ManageOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['rep_phone' => User::normalizePhone($this->input('rep_phone'))]);
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
            $rules += [
                'rep_name' => ['nullable', 'required_with:rep_email', 'string', 'max:255'],
                'rep_email' => ['nullable', 'required_with:rep_name', 'email', 'lowercase', 'max:255', Rule::unique(User::class, 'email')],
                'rep_phone' => ['nullable', 'regex:/^\+639\d{9}$/'],
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
        return ['rep_name' => 'representative name', 'rep_email' => 'representative email', 'rep_phone' => 'representative mobile number'];
    }
}
