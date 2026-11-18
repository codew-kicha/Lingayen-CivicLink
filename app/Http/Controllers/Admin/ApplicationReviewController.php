<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportSheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordHearingRequest;
use App\Http\Requests\RejectApplicationRequest;
use App\Models\Accreditation;
use App\Models\ApplicationModel;
use App\Models\AuditLog;
use App\Notifications\AccreditationFeeChanged;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\CommitteeHearingRecorded;
use App\Services\ApplicationSteps;
use App\Support\ListPagination;
use App\Support\Mailer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ApplicationReviewController extends Controller
{
    /** Filter chips on the queue: value => statuses. "To review" is everything waiting on PESO. */
    public const FILTERS = [
        'review' => ApplicationModel::WITH_OFFICE,
        'requirements' => ['requirements_pending'],
        'hearing' => ['under_review'],
        'approved' => ['approved'],
        'rejected' => ['rejected'],
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ApplicationModel::class);

        return view('admin.applications.index', [
            'applications' => ListPagination::paginate($this->queue($request), $request),
            'direction' => $this->direction($request),
        ]);
    }

    // The queue as a spreadsheet, with the same chip, search and order as the screen.
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', ApplicationModel::class);

        $rows = $this->queue($request)->with(['organization', 'reviewedBy:id,name'])->get()->map(fn (ApplicationModel $application) => [
            $application->organization->name,
            $application->organization->barangay,
            $application->organization->sector,
            $application->type === 'renewal' ? 'Renewal' : 'New',
            Str::headline($application->submission_channel),
            Str::headline($application->status),
            ApplicationModel::PHASES[$application->phase()] ?? 'Closed',
            $application->hearing_outcome ? Str::headline($application->hearing_outcome) : '',
            $application->fee_waived ? 'Waived' : 'PHP '.number_format($application->fee()),
            $application->submitted_at?->toDateString(),
            $application->reviewed_at?->toDateString(),
            $application->reviewedBy?->name,
            $application->flagged_documents_count,
            $application->rejection_reason,
        ]);

        return Excel::download(new ReportSheet('Applications', [
            'Organization', 'Barangay', 'Sector', 'Type', 'Channel', 'Status', 'Phase', 'Hearing outcome', 'Fee',
            'Filed', 'Reviewed', 'Reviewed by', 'Documents to check', 'Rejection reason',
        ], $rows->all()), 'applications-'.now()->format('Y-m-d').'.xlsx');
    }

    /** Waiting applications first, then by filing date; filtered by the chip and the search box. */
    private function queue(Request $request): Builder
    {
        $direction = $this->direction($request);

        return ApplicationModel::with('organization')
            ->where('status', '!=', 'draft') // still being prepared by the organization
            ->withCount(['documents as flagged_documents_count' => fn ($q) => $q->whereIn('ocr_status', ['mismatch', 'unreadable'])])
            ->when(self::FILTERS[$request->string('status')->value()] ?? null, fn ($q, $statuses) => $q->whereIn('status', $statuses))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->whereHas('organization', fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('barangay', 'like', "%{$term}%")))
            // CASE rather than MySQL's FIELD() so the same query runs under SQLite in the test suite.
            ->orderByRaw("CASE WHEN status IN ('".implode("', '", ApplicationModel::WITH_OFFICE)."') THEN 0 ELSE 1 END")
            ->orderBy('submitted_at', $direction)
            ->orderBy('id', $direction);
    }

    private function direction(Request $request): string
    {
        return $request->input('direction') === 'asc' ? 'asc' : 'desc';
    }

    public function show(ApplicationModel $application): View
    {
        $this->authorize('review', $application);
        abort_if($application->status === 'draft', 404);

        return view('admin.applications.show', [
            'application' => $application->load(['organization.members', 'documents', 'submittedBy', 'reviewedBy', 'formApprovedBy', 'hearings']),
        ]);
    }

    /**
     * Step one of the review (pre-final defense panel): the office approves the application form and
     * member list, and only then asks for the other requirements. An application filed on paper may
     * already carry all of them; it goes straight to requirements_submitted.
     */
    public function approveForm(Request $request, ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);
        abort_unless($application->status === 'submitted', 422);

        // A paper filing arrives with its requirements attached; an online one always gets the upload
        // step, even when nothing required is left (a free renewal), so optional ones can be added.
        $complete = $application->submission_channel === 'assisted' && ApplicationSteps::missingUploads($application)->isEmpty();
        $application->update([
            'status' => $complete ? 'requirements_submitted' : 'requirements_pending',
            'form_approved_at' => now(),
            'form_approved_by' => $request->user()->id,
            'requirements_submitted_at' => $complete ? now() : null,
        ]);
        AuditLog::record('application.form_approved', $application->organization, ['application_id' => $application->id]);

        $sent = Mailer::send($application->organization->user, new ApplicationStatusChanged($application));

        return back()->with('status', $complete
            ? 'Application form approved. Its requirements are already attached; validate them, then forward it to the committee.'
            : 'Application form approved. The organization has been asked to upload its requirements.')
            ->with($sent ? [] : ['error' => Mailer::FAILED]);
    }

    /**
     * The office waives the fee on one application, or decides it applies after all (client, Nov
     * 2026). Open until the application goes to the committee. Requiring it once the requirements are
     * in without a receipt reopens them so the organization can add one.
     */
    public function updateFee(Request $request, ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);
        abort_unless(in_array($application->status, ApplicationModel::FEE_OPEN, true), 422);
        $waived = $request->validate(['fee_waived' => ['required', 'boolean']])['fee_waived'];
        $waived = filter_var($waived, FILTER_VALIDATE_BOOLEAN);

        if ($waived === $application->fee_waived) {
            return back();
        }

        $reopen = ! $waived && $application->status === 'requirements_submitted'
            && ! $application->documents()->where('document_type', 'fee_receipt')->exists();

        $application->update(['fee_waived' => $waived] + ($reopen ? [
            'status' => 'requirements_pending',
            'requirements_submitted_at' => null,
            'requirements_note' => 'The accreditation fee applies to this application. Upload the official receipt of the '.$application->feeLabel().' from the Municipal Treasury.',
        ] : []));
        AuditLog::record($waived ? 'application.fee_waived' : 'application.fee_required', $application->organization, ['application_id' => $application->id]);

        // Before the form is approved the organization is not uploading anything yet; the approval tells it what to bring.
        $sent = $application->status === 'submitted'
            || Mailer::send($application->organization->user, new AccreditationFeeChanged($application, $reopen));

        return back()->with('status', match (true) {
            $waived => 'Fee waived. The official receipt is no longer asked for, and the accreditation will default to '.$application->validityYears().' years.',
            $reopen => 'The fee applies. The requirements were reopened so the organization can upload the receipt; it has been told.',
            default => 'The fee applies. The official receipt is required again.',
        })->with($sent ? [] : ['error' => Mailer::FAILED]);
    }

    /** The requirements need fixing (a wrong receipt, an unreadable scan): back to the CSO with a note. */
    public function returnRequirements(Request $request, ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);
        abort_unless($application->status === 'requirements_submitted', 422);
        $note = $request->validate(
            ['requirements_note' => ['required', 'string', 'max:2000']],
            ['requirements_note.required' => 'Say what the organization needs to fix.'],
        )['requirements_note'];

        $application->update(['status' => 'requirements_pending', 'requirements_note' => $note]);
        AuditLog::record('application.requirements_returned', $application->organization, ['application_id' => $application->id]);

        $sent = Mailer::send($application->organization->user, new ApplicationStatusChanged($application));

        return back()->with('status', 'Requirements returned to the organization with your note.')->with($sent ? [] : ['error' => Mailer::FAILED]);
    }

    /**
     * Validation done (client's process, Nov 2026): the form was approved and the requirements check
     * out, so the application goes to the Sangguniang Bayan committee for its hearing.
     */
    public function forwardToCommittee(Request $request, ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);
        abort_unless($application->status === 'requirements_submitted', 422);

        $application->update(['status' => 'under_review', 'hearing_outcome' => null]);
        AuditLog::record('application.forwarded_to_committee', $application->organization, ['application_id' => $application->id]);

        $sent = Mailer::send($application->organization->user, new ApplicationStatusChanged($application));

        return back()->with('status', 'Validated and forwarded to the committee. The organization has been told.')
            ->with($sent ? [] : ['error' => Mailer::FAILED]);
    }

    /**
     * Records the committee hearing after it took place and tells the organization what happened:
     * the date, the outcome and the office's remarks. The latest outcome is kept on the application;
     * a favorable one opens Approve.
     */
    public function recordHearing(RecordHearingRequest $request, ApplicationModel $application): RedirectResponse
    {
        $hearing = DB::transaction(function () use ($application, $request) {
            $hearing = $application->hearings()->create([
                ...$request->safe()->only(['held_on', 'outcome']),
                'committee' => $request->validated('committee') ?: null,
                'remarks' => $request->validated('remarks'),
                'recorded_by' => $request->user()->id,
            ]);
            $application->update(['hearing_outcome' => $hearing->outcome]);

            return $hearing;
        });
        AuditLog::record('application.hearing_recorded', $application->organization, [
            'application_id' => $application->id, 'outcome' => $hearing->outcome,
        ]);

        $sent = Mailer::send($application->organization->user, new CommitteeHearingRecorded($application, $hearing));

        return back()->with('status', 'Hearing recorded ('.strtolower($hearing->outcomeLabel()).'). The organization has been sent your remarks.')
            ->with($sent ? [] : ['error' => Mailer::FAILED]);
    }

    /** A hearing recorded by mistake: removed, the outcome goes back to the one before, nobody is notified. */
    public function undoHearing(ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);
        abort_unless($application->status === 'under_review', 422);

        DB::transaction(function () use ($application) {
            $application->hearings()->reorder()->latest('id')->first()?->delete();
            $application->update(['hearing_outcome' => $application->hearings()->reorder()->latest('id')->value('outcome')]);
        });
        AuditLog::record('application.hearing_undone', $application->organization, ['application_id' => $application->id]);

        return back()->with('status', 'The last hearing record was removed. The organization was not notified of the correction.');
    }

    /**
     * Approving issues the accreditation and closes the application together. If any step fails
     * the whole thing rolls back, so an organization can never end up approved without a
     * matching accreditation record (PRD §8).
     */
    public function approve(Request $request, ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);

        // The Desk Officer sets how long the accreditation runs: three years when filed free (within a
        // free period), one year when the fee applied; the date can be changed on the form.
        $expiresAt = $request->validate([
            'expires_at' => ['nullable', 'date', 'after:today', 'before:'.now()->addYears(10)->toDateString()],
        ], ['expires_at.after' => 'The expiry date must be after today.', 'expires_at.before' => 'Choose an expiry date within ten years.'])['expires_at']
            ?? now()->addYears($application->validityYears())->toDateString();

        // Only once the requirements are in; the hearing check below explains what is still missing.
        abort_unless(in_array($application->status, ['requirements_submitted', 'under_review'], true), 422);

        // The committee hears it first; the office approves only after a favorable hearing.
        if (! $application->readyToApprove()) {
            return back()->with('error', 'Approve once the committee hearing is favorable. It is now: '.Str::lower($application->phaseNote()).'.');
        }

        DB::transaction(function () use ($application, $expiresAt) {
            $application->organization->accreditations()
                ->where('status', 'active')
                ->update(['status' => 'expired', 'active_org_marker' => null]);

            $application->organization->accreditations()->create([
                'application_id' => $application->id,
                'verification_code' => Accreditation::generateVerificationCode(),
                'status' => 'active',
                'active_org_marker' => $application->organization_id,
                'issued_at' => now()->toDateString(),
                'expires_at' => $expiresAt,
            ]);

            $application->update([
                'status' => 'approved',
                'reviewed_by' => request()->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        $sent = Mailer::send($application->organization->user, new ApplicationStatusChanged($application->refresh()));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Application approved and accreditation issued.')
            ->with($sent ? [] : ['error' => Mailer::FAILED]);
    }

    public function reject(RejectApplicationRequest $request, ApplicationModel $application): RedirectResponse
    {
        abort_unless(in_array($application->status, ApplicationModel::OPEN, true), 422);

        $application->update([
            'status' => 'rejected',
            'rejection_reason' => $request->validated('rejection_reason'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $sent = Mailer::send($application->organization->user, new ApplicationStatusChanged($application));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Application rejected and the organization has been notified.')
            ->with($sent ? [] : ['error' => Mailer::FAILED]);
    }
}
