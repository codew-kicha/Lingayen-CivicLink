<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSbStageRequest extends FormRequest
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
            // One reading at a time: forward to the next stage, or back one to correct a mistake.
            'sb_stage' => ['required', Rule::in(array_filter($this->route('application')->neighbouringStages()))],
        ];
    }

    public function messages(): array
    {
        return ['sb_stage.in' => 'Record the readings one at a time: the next stage, or one back to correct a mistake.'];
    }
}
