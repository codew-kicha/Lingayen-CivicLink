<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportSheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectApplicationRequest;
use App\Http\Requests\UpdateSbStageRequest;
use App\Models\Accreditation;
use App\Models\ApplicationModel;
use App\Models\AuditLog;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\SbReadingRecorded;
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
            $application->sb_stage === 'not_endorsed' ? 'Not endorsed yet' : Str::headline($application->sb_stage),
            $application->submitted_at?->toDateString(),
            $application->reviewed_at?->toDateString(),
            $application->reviewedBy?->name,
            $application->flagged_documents_count,
            $application->rejection_reason,
        ]);

        return Excel::download(new ReportSheet('Applications', [
            'Organization', 'Barangay', 'Sector', 'Type', 'Channel', 'Status', 'SB stage',
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
            'application' => $application->load(['organization.members', 'documents', 'submittedBy', 'reviewedBy', 'formApprovedBy']),
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

        $complete = ApplicationSteps::missingUploads($application)->isEmpty();
        $application->update([
            'status' => $complete ? 'requirements_submitted' : 'requirements_pending',
            'form_approved_at' => now(),
            'form_approved_by' => $request->user()->id,
            'requirements_submitted_at' => $complete ? now() : null,
        ]);
        AuditLog::record('application.form_approved', $application->organization, ['application_id' => $application->id]);

        $sent = Mailer::send($application->organization->user, new ApplicationStatusChanged($application));

        return back()->with('status', $complete
            ? 'Application form approved. Its requirements are already attached; record the readings when the SB takes it up.'
            : 'Application form approved. The organization has been asked to upload its requirements.')
            ->with($sent ? [] : ['error' => Mailer::FAILED]);
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
     * Records a Sangguniang Bayan reading and tells the CSO what happened (pre-final defense panel):
     * the reading, the session date, the outcome and the office's remarks. A passed reading moves the
     * application to that stage; deferred or returned leaves it where it was. Going back one stage
     * corrects a mistake: the last recorded reading is removed and the CSO is not notified again.
     */
    public function updateStage(UpdateSbStageRequest $request, ApplicationModel $application): RedirectResponse
    {
        $stage = $request->validated('sb_stage');

        if (! $request->forward()) {
            DB::transaction(function () use ($application, $stage) {
                $application->readings()->where('stage', $application->sb_stage)->where('outcome', 'passed')->reorder()->latest('id')->first()?->delete();
                $application->update(['sb_stage' => $stage]);
            });
            AuditLog::record('application.reading_undone', $application->organization, ['application_id' => $application->id, 'back_to' => $stage]);

            return back()->with('status', 'Moved back to '.strtolower(ApplicationModel::SB_STAGES[$stage]).'. The organization was not notified of the correction.');
        }

        $outcome = $stage === 'endorsed' ? 'passed' : $request->validated('outcome');
        $reading = DB::transaction(function () use ($application, $request, $stage, $outcome) {
            $reading = $application->readings()->create([
                'stage' => $stage,
                'outcome' => $outcome,
                'remarks' => $request->validated('remarks') ?: null,
                'held_on' => $request->validated('held_on'),
                'recorded_by' => $request->user()->id,
            ]);
            $application->update([
                'sb_stage' => $outcome === 'passed' ? $stage : $application->sb_stage,
                'status' => 'under_review',
            ]);

            return $reading;
        });
        AuditLog::record('application.reading_recorded', $application->organization, [
            'application_id' => $application->id, 'stage' => $stage, 'outcome' => $outcome,
        ]);

        $sent = Mailer::send($application->organization->user, new SbReadingRecorded($application, $reading));

        return back()->with('status', $reading->stageLabel().' recorded ('.strtolower($reading->outcomeLabel()).'). The organization has been sent your remarks.')
            ->with($sent ? [] : ['error' => Mailer::FAILED]);
    }

    /**
     * Approving issues the accreditation and closes the application together. If any step fails
     * the whole thing rolls back, so an organization can never end up approved without a
     * matching accreditation record (PRD §8).
     */
    public function approve(Request $request, ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);

        // The Desk Officer sets how long the accreditation runs; three years unless the SB decided otherwise.
        $expiresAt = $request->validate([
            'expires_at' => ['nullable', 'date', 'after:today', 'before:'.now()->addYears(10)->toDateString()],
        ], ['expires_at.after' => 'The expiry date must be after today.', 'expires_at.before' => 'Choose an expiry date within ten years.'])['expires_at']
            ?? now()->addYears(3)->toDateString();

        // Only once the requirements are in; the endorsement check below explains what is still missing.
        abort_unless(in_array($application->status, ['requirements_submitted', 'under_review'], true), 422);

        // The Sangguniang Bayan decides; the office issues the accreditation only after its endorsement.
        if ($application->sb_stage !== 'endorsed') {
            return back()->with('error', 'Approve once the Sangguniang Bayan has endorsed the application. Record each reading first; it is now at '
                .strtolower(ApplicationModel::SB_STAGES[$application->sb_stage] ?? $application->sb_stage).'.');
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
                'sb_stage' => 'endorsed',
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
