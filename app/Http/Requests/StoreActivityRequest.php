<?php

namespace App\Http\Requests;

use App\Models\Activity;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used by a CSO logging its own activity and by PESO's assisted encoding, where the
 * organization comes from the route instead of the signed-in user.
 */
class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('organization') instanceof Organization
            ? $this->user()->can('manage', Organization::class)
            : $this->user()->can('create', Activity::class);
    }

    public function organization(): Organization
    {
        return $this->route('organization') ?? $this->user()->organization;
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
            'activity_source' => ['required', Rule::in(array_keys(Activity::SOURCES))],
            'partners' => ['nullable', 'array', 'max:10'],
            'partners.*' => [
                'integer', 'distinct',
                Rule::exists('organizations', 'id'),
                Rule::notIn([$this->organization()->id]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'activity_date.before_or_equal' => 'Log activities after they have taken place, not before.',
            'activity_source.required' => 'Say who organized the activity.',
            'partners.max' =>'Tag at most 10 partner organizations.',
            'partners.*.not_in' => 'An organization cannot be its own partner.',
        ];
    }
}
