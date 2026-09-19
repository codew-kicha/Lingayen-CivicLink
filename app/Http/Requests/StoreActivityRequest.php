<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Activity::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'participants_estimate' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'activity_date.before_or_equal' => 'Log activities after they have taken place, not before.',
        ];
    }
}
