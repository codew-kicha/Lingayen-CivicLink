<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

// An admin correcting a CSO representative's login (name, email, mobile) and, optionally, setting a
// new password for them. The admin re-enters their own password, since this hands over an account.
class UpdateCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageCredentials', $this->route('user'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => User::normalizePhone($this->input('phone'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique(User::class)->ignore($this->route('user'))],
            'phone' => ['nullable', 'regex:/^\+639\d{9}$/'],
            'reset_password' => ['boolean'],
            'new_password' => ['exclude_unless:reset_password,1', 'required', Password::defaults()],
            'admin_password' => ['required', 'current_password'],
        ];
    }

    /**
     * The usual slip: typing (or a browser filling in) the representative's password instead of the
     * admin's own. Say so plainly rather than just "not your password".
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $typed = (string) $this->input('admin_password');

            if ($validator->errors()->has('admin_password') && $typed !== ''
                && Hash::check($typed, $this->route('user')->password)) {
                $validator->errors()->forget('admin_password');
                $validator->errors()->add('admin_password', "That is {$this->route('user')->name}'s current password. Enter your own admin password, the one you signed in with.");
            }
        }];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a Philippine mobile number, for example 0917 123 4567.',
            'admin_password.current_password' => 'That is not your admin password. Use the one you signed in with.',
        ];
    }

    public function attributes(): array
    {
        return ['new_password' => 'new password', 'admin_password' => 'your password'];
    }
}
