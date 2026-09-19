<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('verify', $this->route('activity'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Give the organization a reason so they know what to correct.',
        ];
    }
}
