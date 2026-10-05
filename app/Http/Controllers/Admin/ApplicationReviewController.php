<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectApplicationRequest;
use App\Http\Requests\UpdateSbStageRequest;
use App\Models\Accreditation;
use App\Models\ApplicationModel;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationReviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ApplicationModel::class);

        return view('admin.applications.index', [
            'applications' => ApplicationModel::with('organization')
                ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
                ->when($request->input('stage'), fn ($q, $stage) => $q->where('sb_stage', $stage))
                // Unreviewed applications first. CASE rather than MySQL's FIELD() so the same
                // query runs under SQLite in the test suite.
                ->orderByRaw("CASE status
                    WHEN 'submitted' THEN 1
                    WHEN 'under_review' THEN 2
                    WHEN 'approved' THEN 3
                    WHEN 'rejected' THEN 4
                    ELSE 5 END")
                ->latest('submitted_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
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
        $application->update([
            'sb_stage' => $request->validated('sb_stage'),
            'status' => $application->status === 'submitted' ? 'under_review' : $application->status,
        ]);

        $application->organization->user?->notify(new ApplicationStatusChanged($application));

        return back()->with('status', 'Reading stage updated.');
    }

    /**
     * Approving issues the accreditation and closes the application together. If any step fails
     * the whole thing rolls back, so an organization can never end up approved without a
     * matching accreditation record (PRD §8).
     */
    public function approve(ApplicationModel $application): RedirectResponse
    {
        $this->authorize('review', $application);

        abort_unless(in_array($application->status, ['submitted', 'under_review'], true), 422);

        DB::transaction(function () use ($application) {
            $application->organization->accreditations()
                ->where('status', 'active')
                ->update(['status' => 'expired', 'active_org_marker' => null]);

            $application->organization->accreditations()->create([
                'application_id' => $application->id,
                'verification_code' => Accreditation::generateVerificationCode(),
                'status' => 'active',
                'active_org_marker' => $application->organization_id,
                'issued_at' => now()->toDateString(),
                'expires_at' => now()->addYears(3)->toDateString(),
            ]);

            $application->update([
                'status' => 'approved',
                'sb_stage' => 'endorsed',
                'reviewed_by' => request()->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        $application->organization->user?->notify(new ApplicationStatusChanged($application->refresh()));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Application approved and accreditation issued.');
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

        $application->organization->user?->notify(new ApplicationStatusChanged($application));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Application rejected and the organization has been notified.');
    }
}
