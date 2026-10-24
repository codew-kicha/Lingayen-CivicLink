<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectActivityRequest;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Notifications\ActivityVerified;
use App\Services\PerformanceScoreCalculator;
use App\Support\ListPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityVerificationController extends Controller
{
    /** Filter chips: the review queue first, then what was already decided. */
    public const FILTERS = ['pending' => 'Waiting', 'verified' => 'Verified', 'rejected' => 'Rejected'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $status = array_key_exists($request->string('status')->value(), self::FILTERS) ? $request->string('status')->value() : 'pending';

        return view('admin.activities.index', [
            'activities' => ListPagination::paginate(Activity::with(['organization', 'loggedBy', 'verifiedBy:id,name', 'partnerOrganizations:id,name'])
                ->where('status', $status)
                ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($q) => $q
                    ->where('title', 'like', "%{$term}%")
                    ->orWhereHas('organization', fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('barangay', 'like', "%{$term}%"))))
                // The queue is worked oldest first; decided activities show the latest decisions first.
                ->when($status === 'pending',
                    fn ($q) => $q->oldest('created_at')->oldest('id'),
                    fn ($q) => $q->latest('verified_at')->latest('id')),
                $request),
            'currentStatus' => $status,
            'pendingCount' => Activity::where('status', 'pending')->count(),
        ]);
    }

    // Both decisions answer JSON to the review dialog (which sends them after its Undo window) and
    // redirect for a plain form post.
    public function verify(Request $request, Activity $activity, PerformanceScoreCalculator $scores): RedirectResponse|JsonResponse
    {
        $this->authorize('verify', $activity);

        abort_unless($activity->status === 'pending', 422, 'This activity has already been reviewed.');

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

        return $this->done($request, 'Activity verified and the score has been recalculated.');
    }

    public function reject(RejectActivityRequest $request, Activity $activity): RedirectResponse|JsonResponse
    {
        abort_unless($activity->status === 'pending', 422, 'This activity has already been reviewed.');

        $activity->update([
            'status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_reason' => $request->validated('rejection_reason'),
        ]);

        $activity->organization->user?->notify(new ActivityVerified($activity));

        return $this->done($request, 'Activity rejected and the organization has been notified.');
    }

    private function done(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }
}
