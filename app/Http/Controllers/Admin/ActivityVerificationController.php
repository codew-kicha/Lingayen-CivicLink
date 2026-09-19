<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectActivityRequest;
use App\Models\Activity;
use App\Notifications\ActivityVerified;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        return view('admin.activities.index', [
            'activities' => Activity::with(['organization', 'loggedBy'])
                ->where('status', $request->input('status', 'pending'))
                ->latest('activity_date')
                ->paginate(20)
                ->withQueryString(),
            'currentStatus' => $request->input('status', 'pending'),
            'pendingCount' => Activity::where('status', 'pending')->count(),
        ]);
    }

    public function verify(Activity $activity, PerformanceScoreCalculator $scores): RedirectResponse
    {
        $this->authorize('verify', $activity);

        abort_unless($activity->status === 'pending', 422);

        $activity->update([
            'status' => 'verified',
            'verified_by' => request()->user()->id,
            'verified_at' => now(),
        ]);

        $scores->recalculate($activity->organization);
        $activity->organization->user->notify(new ActivityVerified($activity));

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

        $activity->organization->user->notify(new ActivityVerified($activity));

        return back()->with('status', 'Activity rejected and the organization has been notified.');
    }
}
