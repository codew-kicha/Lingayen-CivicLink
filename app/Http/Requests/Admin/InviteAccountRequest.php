<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// A new person (admin, or the representative attached to an existing organization).
class InviteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => User::normalizePhone($this->input('phone')),
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) : $this->input('email'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...User::nameRules(),
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'regex:/^\+639\d{9}$/'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Enter a Philippine mobile number, for example 0917 123 4567.'];
    }
}
