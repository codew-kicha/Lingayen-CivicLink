<?php

namespace App\Http\Requests;

use App\Models\Activity;
use App\Models\ActivityPhoto;
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
            'venue' => ['nullable', 'string', 'max:150'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'participants_estimate' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remove_evidence' => ['boolean'],
            // What the browser read from the sign-in sheet, for the checks only; never stored.
            'evidence_text' => ['nullable', 'string', 'max:20000'],
            // Up to three photos of the activity, counting the ones already attached and kept.
            'photos' => ['nullable', 'array', 'max:'.ActivityPhoto::MAX],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['integer'],
            'partners' => ['nullable', 'array', 'max:10'],
            'partners.*' => [
                'integer', 'distinct',
                Rule::exists('accreditations', 'organization_id')->where('status', 'active'),
                Rule::notIn([$this->organization()->id]),
            ],
        ];
    }

    /** With the photos already attached (less the ones being removed), no more than three. */
    public function after(): array
    {
        return [function ($validator) {
            $activity = $this->route('activity');
            $kept = $activity instanceof Activity
                ? $activity->photos()->whereKeyNot(array_map('intval', (array) $this->input('remove_photos', [])))->count()
                : 0;
            if ($kept + count((array) $this->file('photos', [])) > ActivityPhoto::MAX) {
                $validator->errors()->add('photos', 'Attach at most '.ActivityPhoto::MAX.' photos'.($kept ? " ({$kept} already attached)." : '.'));
            }
        }];
    }

    public function messages(): array
    {
        return [
            'photos.max' => 'Attach at most '.ActivityPhoto::MAX.' photos.',
            'photos.*.mimes' => 'Photos must be JPG, PNG or WebP images.',
            'photos.*.max' => 'Each photo must be 5 MB or smaller.',
            'venue.max' => 'Keep the venue under 150 characters.',
            'activity_date.before_or_equal' => 'Log activities after they have taken place, not before.',
            'evidence.mimes' => 'The sign-in sheet must be a PDF, JPG, or PNG file.',
            'evidence.max' => 'The sign-in sheet must be 5 MB or smaller.',
            'partners.max' => 'Tag at most 10 partner organizations.',
            'partners.*.not_in' => 'An organization cannot be its own partner.',
            'partners.*.exists' => 'Only accredited organizations can be tagged as partners.',
        ];
    }
}
