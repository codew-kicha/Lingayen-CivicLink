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
            'sb_stage' => ['required', Rule::in([
                'not_endorsed', 'first_reading', 'second_reading', 'third_reading', 'endorsed',
            ])],
        ];
    }
}
