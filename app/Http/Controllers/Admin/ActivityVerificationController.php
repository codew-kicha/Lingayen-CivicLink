<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Mailer;
use App\Http\Requests\RejectActivityRequest;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Notifications\ActivityVerified;
use App\Services\PerformanceScoreCalculator;
use App\Services\SheetReader;
use App\Support\ListPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityVerificationController extends Controller
{
    /** Filter chips: the review queue first, then what was already decided. */
    public const FILTERS = ['pending' => 'Waiting', 'verified' => 'Verified', 'rejected' => 'Rejected'];

    /** On the waiting list: what the checks found (App\Services\SheetChecks, SheetReader). */
    public const CHECKS = ['consistent' => 'Consistent', 'flagged' => 'Flagged', 'unchecked' => 'Not checked'];

    /** Sheets read by the AI per click of "Read new sheets", so one request stays short. */
    public const READ_BATCH = 5;

    /** Sort orders for the list. The waiting queue defaults to "waiting longest", decided lists to the latest decisions. */
    public const SORTS = [
        'waiting' => 'Waiting longest',
        'logged' => 'Logged most recently',
        'held_new' => 'Held: newest first',
        'held_old' => 'Held: oldest first',
        'decided' => 'Decided most recently',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $status = array_key_exists($request->string('status')->value(), self::FILTERS) ? $request->string('status')->value() : 'pending';
        $sorts = $status === 'pending' ? Arr::except(self::SORTS, 'decided') : self::SORTS;
        $sort = array_key_exists($request->string('sort')->value(), $sorts) ? $request->string('sort')->value() : ($status === 'pending' ? 'waiting' : 'decided');
        // "Held from / to" bounds on the activity date; anything that isn't a real date is ignored.
        $date = fn (string $key) => rescue(fn () => Carbon::createFromFormat('Y-m-d', $request->string($key)->value())->toDateString(), null, false);
        [$from, $to] = [$request->filled('from') ? $date('from') : null, $request->filled('to') ? $date('to') : null];
        $check = $status === 'pending' && array_key_exists($request->string('check')->value(), self::CHECKS) ? $request->string('check')->value() : null;
        $waiting = fn () => Activity::where('status', 'pending');

        return view('admin.activities.index', [
            'activities' => ListPagination::paginate(Activity::with(['organization', 'loggedBy', 'verifiedBy:id,name', 'partnerOrganizations:id,name'])
                ->where('status', $status)
                ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($q) => $q
                    ->where('title', 'like', "%{$term}%")
                    ->orWhereHas('organization', fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('barangay', 'like', "%{$term}%"))))
                ->when($from, fn ($q, $from) => $q->whereDate('activity_date', '>=', $from))
                ->when($to, fn ($q, $to) => $q->whereDate('activity_date', '<=', $to))
                ->when($check, fn ($q, $check) => $check === 'unchecked' ? $q->whereNull('check_status') : $q->where('check_status', $check))
                ->tap(fn ($q) => match ($sort) {
                    'logged' => $q->latest('created_at')->latest('id'),
                    'held_new' => $q->latest('activity_date')->latest('id'),
                    'held_old' => $q->oldest('activity_date')->oldest('id'),
                    'decided' => $q->latest('verified_at')->latest('id'),
                    default => $q->oldest('created_at')->oldest('id'),   // waiting longest: the fair queue order
                }),
                $request),
            'currentStatus' => $status,
            'sorts' => $sorts,
            'currentSort' => $sort,
            'from' => $from,
            'to' => $to,
            'pendingCount' => Activity::where('status', 'pending')->count(),
            'currentCheck' => $check,
            'checkCounts' => [
                'consistent' => $waiting()->where('check_status', 'consistent')->count(),
                'flagged' => $waiting()->where('check_status', 'flagged')->count(),
                'unchecked' => $waiting()->whereNull('check_status')->count(),
            ],
            'aiEnabled' => SheetReader::enabled(),
            'unreadSheets' => SheetReader::enabled() ? $waiting()->whereNotNull('evidence_path')->whereNull('ai_checked_at')->count() : 0,
        ]);
    }

    /**
     * Verifies every waiting activity the checks found consistent, in one go (pre-final defense panel:
     * many activities shouldn't mean many clicks). Flagged ones still need a look. Each organization is
     * rescored once and told by email after the page has answered.
     */
    public function verifyConsistent(Request $request, PerformanceScoreCalculator $scores): RedirectResponse
    {
        $this->authorize('viewAny', Activity::class);

        $activities = Activity::with(['organization.user', 'partnerOrganizations'])
            ->where('status', 'pending')->where('check_status', 'consistent')->get()
            ->filter(fn (Activity $activity) => $request->user()->can('verify', $activity));
        if ($activities->isEmpty()) {
            return back()->with('error', 'No waiting activity is marked consistent.');
        }

        Activity::whereKey($activities->modelKeys())->update([
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $activities->flatMap(fn (Activity $a) => $a->partnerOrganizations->prepend($a->organization))
            ->unique('id')->each(fn ($organization) => $scores->recalculate($organization));
        AuditLog::record('activity.bulk_verified', null, ['count' => $activities->count(), 'ids' => $activities->modelKeys()]);
        \Illuminate\Support\defer(fn () => $activities->each(fn (Activity $a) => Mailer::send($a->organization->user, new ActivityVerified($a->fresh()))));

        return back()->with('status', $activities->count().' consistent '.str('activity')->plural($activities->count()).' verified and scores recalculated.');
    }

    /** Reads up to READ_BATCH waiting sheets with the AI (those it hasn't read yet). */
    public function readSheets(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Activity::class);
        abort_unless(SheetReader::enabled(), 404);

        $read = Activity::where('status', 'pending')->whereNotNull('evidence_path')->whereNull('ai_checked_at')
            ->oldest('created_at')->limit(self::READ_BATCH)->get()
            ->filter(fn (Activity $activity) => SheetReader::read($activity))->count();

        return back()->with($read ? 'status' : 'error', $read
            ? "Read {$read} ".str('sheet')->plural($read).'.'
            : 'No sheet could be read just now. Try again in a minute.');
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
        $sent = Mailer::send($activity->organization->user, new ActivityVerified($activity));

        return $this->done($request, 'Activity verified and the score has been recalculated.', $sent);
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

        $sent = Mailer::send($activity->organization->user, new ActivityVerified($activity));

        return $this->done($request, 'Activity rejected and the organization has been notified.', $sent);
    }

    private function done(Request $request, string $message, bool $mailed = true): RedirectResponse|JsonResponse
    {
        if (! $mailed) {
            $message .= ' The email to the organization could not be sent.';
        }

        return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }
}
