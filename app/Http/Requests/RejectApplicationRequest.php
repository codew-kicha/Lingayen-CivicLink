<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('application'));
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
            'rejection_reason.min' => 'Give a little more detail so the organization knows what to correct.',
        ];
    }
}
