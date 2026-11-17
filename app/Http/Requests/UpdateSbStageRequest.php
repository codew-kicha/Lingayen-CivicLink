<?php

namespace App\Http\Requests;

use App\Models\ApplicationReading;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recording a Sangguniang Bayan reading (pre-final defense panel: the CSO gets feedback after each
 * one). Forward to the next stage needs the session date, the outcome and remarks; the endorsement
 * needs only the date. Back one stage is a correction and needs nothing else.
 */
class UpdateSbStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('application'));
    }

    /** True when this records the next reading rather than correcting the last one. */
    public function forward(): bool
    {
        return $this->input('sb_stage') === $this->route('application')->neighbouringStages()[1];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reading = $this->forward() && $this->input('sb_stage') !== 'endorsed';

        return [
            // One reading at a time: forward to the next stage, or back one to correct a mistake.
            'sb_stage' => ['required', Rule::in(array_filter($this->route('application')->neighbouringStages()))],
            'outcome' => $reading ? ['required', Rule::in(array_keys(ApplicationReading::OUTCOMES))] : ['exclude'],
            'remarks' => $reading ? ['required', 'string', 'max:2000'] : ($this->forward() ? ['nullable', 'string', 'max:2000'] : ['exclude']),
            'held_on' => $this->forward() ? ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'] : ['exclude'],
        ];
    }

    public function messages(): array
    {
        return [
            'sb_stage.in' => 'Record the readings one at a time: the next stage, or one back to correct a mistake.',
            'outcome.required' => 'Choose what happened at this reading.',
            'remarks.required' => 'Write the feedback for the organization: what was discussed, and anything they need to do.',
            'held_on.required' => 'Enter the date of the Sangguniang Bayan session.',
            'held_on.before_or_equal' => 'The session date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // The readings begin once the requirements are in (pre-final defense panel's order); refused
        // before anything is validated, so an application that isn't ready never reaches the form rules.
        // (Someone who may not review at all is still refused with 403 by authorize(), which runs next.)
        abort_if($this->authorize() && ! in_array($this->route('application')->status, ['requirements_submitted', 'under_review'], true), 422);

        if (is_string($this->input('remarks'))) {
            $this->merge(['remarks' => trim($this->input('remarks'))]);
        }
    }
}
