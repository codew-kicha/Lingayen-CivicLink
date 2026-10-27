<?php

namespace App\Http\Requests;

use App\Models\Activity;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used by a CSO logging or editing its own activity, and by PESO's assisted encoding, where the
 * organization comes from the route instead of the signed-in user.
 */
class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('activity') instanceof Activity) {
            return $this->user()->can('update', $this->route('activity'));
        }

        // Office staff encoding for an organization: it must be accredited, as for the CSO itself.
        return $this->route('organization') instanceof Organization
            ? $this->user()->can('manage', Organization::class) && $this->route('organization')->isAccredited()
            : $this->user()->can('create', Activity::class);
    }

    public function organization(): Organization
    {
        return $this->route('organization') ?? $this->route('activity')?->organization ?? $this->user()->organization;
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
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remove_evidence' => ['boolean'],
            'partners' => ['nullable', 'array', 'max:10'],
            'partners.*' => [
                'integer', 'distinct',
                Rule::exists('accreditations', 'organization_id')->where('status', 'active'),
                Rule::notIn([$this->organization()->id]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'activity_date.before_or_equal' => 'Log activities after they have taken place, not before.',
            'activity_source.required' => 'Say who organized the activity.',
            'evidence.mimes' => 'The sign-in sheet must be a PDF, JPG, or PNG file.',
            'evidence.max' => 'The sign-in sheet must be 5 MB or smaller.',
            'partners.max' =>'Tag at most 10 partner organizations.',
            'partners.*.not_in' => 'An organization cannot be its own partner.',
            'partners.*.exists' => 'Only accredited organizations can be tagged as partners.',
        ];
    }
}
