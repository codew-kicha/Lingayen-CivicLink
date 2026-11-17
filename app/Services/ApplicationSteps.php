<?php

namespace App\Services;

use App\Models\ApplicationModel;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Registration and the accreditation application as one guided path (pre-final defense panel):
 * account, email code, members, the accreditation form filled in online, then review and submit.
 * Steps 3 to 5 work on a draft application, so everything is saved as the representative goes and
 * they can stop and come back. Submitting produces the accreditation form and the officer list from
 * what was entered, and the application joins the Desk Office's queue.
 *
 * The other requirements (official receipt, constitution, DOLE/SEC) come after: the panel asked
 * that nothing be paid or uploaded before the Desk Office approves the application form. Step 6
 * opens only then (status requirements_pending), and step 7 is the Sangguniang Bayan's readings.
 */
class ApplicationSteps
{
    /** Every step, in order; the first two happen at sign-up, the last is the SB's. */
    public const STEPS = [
        'account' => 'Account',
        'email' => 'Confirm email',
        'members' => 'Members',
        'form' => 'Accreditation form',
        'review' => 'Review and submit',
        'requirements' => 'Requirements',
        'readings' => 'SB readings',
    ];

    /** The draft's pages, before the application is sent. */
    public const PAGES = ['members', 'form', 'review'];

    public static function draft(Organization $organization): ?ApplicationModel
    {
        return $organization->applications()->where('status', 'draft')->latest('id')->first();
    }

    /** An application sent and not yet decided: no second one until it is. */
    public static function inReview(Organization $organization): ?ApplicationModel
    {
        return $organization->applications()->whereIn('status', ApplicationModel::OPEN)->latest('id')->first();
    }

    /** The application whose form the Desk Office approved, waiting for the requirements. */
    public static function awaitingRequirements(Organization $organization): ?ApplicationModel
    {
        return $organization->applications()->where('status', 'requirements_pending')->latest('id')->first();
    }

    /**
     * A new draft. Renewal when the organization holds or held an accreditation; the form starts from
     * the organization, the representative, and any earlier application's answers (a correction).
     */
    public static function start(Organization $organization, User $user): ApplicationModel
    {
        $previous = $organization->applications()->where('status', '!=', 'draft')->latest('id')->first();

        return $organization->applications()->create([
            'type' => $organization->accreditations()->whereIn('status', ['active', 'expired'])->exists() ? 'renewal' : 'new',
            'status' => 'draft',
            'submission_channel' => 'online',
            'submitted_by' => $user->id,
            'form_data' => array_merge(self::formDefaults($organization, $user), array_intersect_key(
                $previous?->form_data ?? [], array_flip(['organization_name', 'representative_name', 'contact_number', 'place_barangay', 'place_detail', 'place_of_operation'])
            )),
        ]);
    }

    public static function formDefaults(Organization $organization, User $user): array
    {
        return [
            'organization_name' => $organization->name,
            'representative_name' => $user->name,
            'contact_number' => self::localPhone($user->phone),
            'place_barangay' => $organization->barangay,
            'place_detail' => '',
            'place_of_operation' => self::placeOfOperation($organization->barangay),
        ];
    }

    /**
     * The Place of Operation as written on the form: the specific place (purok, street, landmark) if
     * given, then the barangay chosen from Lingayen's list.
     */
    public static function placeOfOperation(string $barangay, ?string $detail = null): string
    {
        return (filled($detail) ? trim($detail).', ' : '')."Barangay {$barangay}, Lingayen, Pangasinan";
    }

    /** +639171234567 as 0917 123 4567. */
    public static function localPhone(?string $phone): string
    {
        return preg_match('/^\+63(9\d{2})(\d{3})(\d{4})$/', (string) $phone, $m) ? "0{$m[1]} {$m[2]} {$m[3]}" : (string) $phone;
    }

    /** The requirements the applicant uploads for this application's type (key => type config). */
    public static function uploads(ApplicationModel $application): array
    {
        return collect(config('document_types'))
            ->reject(fn (array $type) => ! empty($type['made_online']))
            ->reject(fn (array $type) => ! empty($type['new_only']) && $application->type !== 'new')
            ->all();
    }

    /** The required uploads not yet attached to the application (keys). */
    public static function missingUploads(ApplicationModel $application): Collection
    {
        return collect(self::uploads($application))->reject(fn (array $type) => $type['optional'])->keys()
            ->diff($application->documents()->pluck('document_type'))->values();
    }

    /** @return array<string, bool> which of the draft's pages are complete */
    public static function done(ApplicationModel $application): array
    {
        $members = $application->organization->members()->get(['position'])->toArray();

        $done = [
            'members' => count($members) >= OrganizationMember::MINIMUM && OrganizationMember::representativeProblem($members) === null,
            'form' => filled($application->form_data['certified_at'] ?? null),
        ];

        return $done + ['review' => ! in_array(false, $done, true)];
    }

    /** The first page not yet complete; review once the rest are. */
    public static function current(ApplicationModel $application): string
    {
        return collect(self::done($application))->except('review')->search(false) ?: 'review';
    }

    /** "Step 4 of 7" for a page. */
    public static function number(string $step): int
    {
        return array_search($step, array_keys(self::STEPS), true) + 1;
    }

    /** The form as the Sangguniang Bayan's layout, from the answers given; a preview until $final. */
    public static function formPdf(ApplicationModel $application, bool $final = false): \Barryvdh\DomPDF\PDF
    {
        $requirements = collect(config('document_types'))
            ->reject(fn (array $type) => ! empty($type['new_only']) && $application->type !== 'new');

        return Pdf::loadView('pdf.accreditation-form', [
            'application' => $application,
            'form' => $application->form_data ?? [],
            'requirements' => $requirements,
            'final' => $final,
        ]);
    }

    /**
     * The requirements are in: back to the Desk Office for checking, then the SB readings. A note
     * from an earlier return is cleared.
     */
    public static function submitRequirements(ApplicationModel $application): void
    {
        $application->update(['status' => 'requirements_submitted', 'requirements_submitted_at' => now(), 'requirements_note' => null]);

        AuditLog::record('application.requirements_submitted', $application->organization, ['application_id' => $application->id]);
    }

    /**
     * Sends the draft to the Desk Office: the accreditation form and the officer list are produced as
     * documents (marked 'generated', so the pre-check doesn't apply), then it joins the review queue.
     * The other requirements wait until the form is approved.
     */
    public static function submit(ApplicationModel $application, User $user): void
    {
        $organization = $application->organization;

        DB::transaction(function () use ($application, $organization, $user) {
            // A failed earlier attempt may have left generated copies behind.
            $application->documents()->where('ocr_status', 'generated')->get()->each(function (Document $document) {
                Storage::delete($document->file_path);
                $document->delete();
            });

            $made = [
                'accreditation_form' => [self::formPdf($application, final: true)->output(), 'Accreditation Form ('.($application->type === 'new' ? 'new application' : 'renewal').').pdf'],
                'officers_members_list' => [Pdf::loadView('pdf.officers-members', [
                    'organization' => $organization,
                    'members' => $organization->members()->orderByRaw('CASE WHEN position = ? THEN 0 ELSE 1 END', [OrganizationMember::REPRESENTATIVE])->orderBy('id')->get(),
                    'asOf' => now(),
                ])->output(), 'List of Officers and Members.pdf'],
            ];

            foreach ($made as $type => [$pdf, $filename]) {
                $path = "documents/{$organization->id}/".Str::uuid().'.pdf';
                Storage::put($path, $pdf);
                $organization->documents()->create([
                    'application_id' => $application->id,
                    'document_type' => $type,
                    'file_path' => $path,
                    'original_filename' => $filename,
                    'mime_type' => 'application/pdf',
                    'ocr_status' => 'generated',
                    'ocr_details' => ['generated' => true],
                ]);
            }

            $application->update([
                'status' => 'submitted',
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);
        });

        AuditLog::record('application.submitted', $organization, ['application_id' => $application->id, 'type' => $application->type]);
    }
}
