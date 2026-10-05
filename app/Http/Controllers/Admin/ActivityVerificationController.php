<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectActivityRequest;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Notifications\ActivityVerified;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        return view('admin.activities.index', [
            'activities' => Activity::with(['organization', 'loggedBy', 'partnerOrganizations:id,name'])
                ->where('status', $request->input('status', 'pending'))
                ->latest('activity_date')
                ->paginate(20)
                ->withQueryString(),
            'currentStatus' => $request->input('status', 'pending'),
            'pendingCount' => Activity::where('status', 'pending')->count(),
        ]);
    }

    public function verify(Request $request, Activity $activity, PerformanceScoreCalculator $scores): RedirectResponse
    {
        $this->authorize('verify', $activity);

        abort_unless($activity->status === 'pending', 422);

        // The organization's own claim of who organized it is confirmed here, since a CSO has a
        // reason to call LGU events its own. Corrections are kept in the audit log.
        $source = $request->validate([
            'activity_source' => ['required', Rule::in(array_keys(Activity::SOURCES))],
        ])['activity_source'];

        if ($activity->activity_source !== $source) {
            AuditLog::record('activity.source_corrected', $activity, ['from' => $activity->activity_source, 'to' => $source]);
        }

        $activity->update([
            'activity_source' => $source,
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // Verifying an activity also confirms its partner tags, so every credited organization is rescored.
        foreach ($activity->partnerOrganizations->prepend($activity->organization) as $organization) {
            $scores->recalculate($organization);
        }
        $activity->organization->user?->notify(new ActivityVerified($activity));

        return back()->with('status', 'Activity verified and the score has been recalculated.');
    }

    public function reject(RejectActivityRequest $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->status === 'pending', 422);

        $activity->update([
            'status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_reason' => $request->validated('rejection_reason'),
        ]);

        $activity->organization->user?->notify(new ActivityVerified($activity));

        return back()->with('status', 'Activity rejected and the organization has been notified.');
    }
}
