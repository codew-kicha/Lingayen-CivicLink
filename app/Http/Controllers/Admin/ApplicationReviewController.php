<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportSheet;
use App\Http\Controllers\Controller;
use App\Support\Mailer;
use App\Http\Requests\RejectApplicationRequest;
use App\Http\Requests\UpdateSbStageRequest;
use App\Models\Accreditation;
use App\Models\ApplicationModel;
use App\Notifications\ApplicationStatusChanged;
use App\Support\ListPagination;
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
    /** Filter chips on the queue: value => statuses. "Under review" is everything still waiting on PESO. */
    public const FILTERS = [
        'review' => ['submitted', 'under_review'],
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
            ->withCount(['documents as flagged_documents_count' => fn ($q) => $q->whereIn('ocr_status', ['mismatch', 'unreadable'])])
            ->when(self::FILTERS[$request->string('status')->value()] ?? null, fn ($q, $statuses) => $q->whereIn('status', $statuses))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->whereHas('organization', fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('barangay', 'like', "%{$term}%")))
            // CASE rather than MySQL's FIELD() so the same query runs under SQLite in the test suite.
            ->orderByRaw("CASE WHEN status IN ('submitted', 'under_review') THEN 0 ELSE 1 END")
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

        return view('admin.applications.show', [
            'application' => $application->load(['organization.members', 'documents', 'submittedBy', 'reviewedBy']),
        ]);
    }

    public function updateStage(UpdateSbStageRequest $request, ApplicationModel $application): RedirectResponse
    {
        abort_unless(in_array($application->status, ['submitted', 'under_review'], true), 422);

        $application->update([
            'sb_stage' => $request->validated('sb_stage'),
            'status' => $application->status === 'submitted' ? 'under_review' : $application->status,
        ]);

        $sent = Mailer::send($application->organization->user, new ApplicationStatusChanged($application));

        return back()->with('status', 'Reading stage updated.')->with($sent ? [] : ['error' => Mailer::FAILED]);
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

        abort_unless(in_array($application->status, ['submitted', 'under_review'], true), 422);

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
        abort_unless(in_array($application->status, ['submitted', 'under_review'], true), 422);

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
