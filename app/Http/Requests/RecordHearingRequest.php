<?php

namespace App\Http\Requests;

use App\Models\ApplicationHearing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recording the Sangguniang Bayan committee hearing after it took place (client's process, Nov
 * 2026): the date, the outcome, the committee if known, and the remarks the organization receives.
 */
class RecordHearingRequest extends FormRequest
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
            'held_on' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'outcome' => ['required', Rule::in(array_keys(ApplicationHearing::OUTCOMES))],
            'committee' => ['nullable', 'string', 'max:120'],
            'remarks' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'held_on.required' => 'Enter the date of the hearing.',
            'held_on.before_or_equal' => 'The hearing date cannot be in the future.',
            'outcome.required' => 'Choose the outcome of the hearing.',
            'remarks.required' => 'Write the feedback for the organization: what the committee said, and anything they need to do.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only an application forwarded to the committee can be heard. Refused before the form rules;
        // someone who may not review at all still gets 403 from authorize(), which runs next.
        abort_if($this->authorize() && $this->route('application')->status !== 'under_review', 422);

        $this->merge(collect(['remarks', 'committee'])
            ->filter(fn ($field) => is_string($this->input($field)))
            ->mapWithKeys(fn ($field) => [$field => trim($this->input($field))])
            ->all());
    }
}
