<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LguEvent;
use App\Models\Organization;
use App\Services\NoticeSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * LGU events (pre-final defense panel): activities the LGU organizes are the office's to record, not
 * the CSOs'. The office creates the event, may announce it as a notice, and once it is held ticks the
 * organizations that attended; each one is credited automatically (LguEvent::recordAttendance).
 */
class LguEventController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage', Organization::class);
        $events = LguEvent::withCount('attendance')->with('announcement')->orderByDesc('held_on')->orderByDesc('id')->get();

        return view('admin.lgu-events.index', [
            'upcoming' => $events->filter(fn (LguEvent $event) => ! $event->isHeld())->sortBy('held_on')->values(),
            'held' => $events->filter(fn (LguEvent $event) => $event->isHeld())->values(),
            'choices' => Organization::whereNotNull('user_id')->orderBy('name')->get(['id', 'name', 'barangay']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', Organization::class);
        $announce = $request->boolean('announce');
        $data = $request->validate($this->rules() + ($announce ? NoticeSender::rules($request) + ['email' => ['nullable', 'boolean']] : []), $this->messages());

        $event = LguEvent::create([...$this->details($data), 'created_by' => $request->user()->id]);
        AuditLog::record('lgu_event.created', $event, ['title' => $event->title, 'held_on' => $event->held_on->toDateString()]);

        $sent = null;
        if ($announce) {
            $sent = NoticeSender::send($request->user(), "LGU event: {$event->title}", $this->noticeBody($event),
                $data['audience'], $data['audience_value'] ?? null, $request->boolean('email'));
            $event->update(['notice_id' => $sent?->id]);
        }

        return redirect()->route('admin.lgu-events.show', $event)->with('status', match (true) {
            $sent !== null => "LGU event saved and announced to {$sent->recipients} ".str('organization')->plural($sent->recipients).'.',
            $announce => 'LGU event saved. It was not announced: no organization account matches that audience.',
            default => 'LGU event saved.',
        });
    }

    public function show(LguEvent $event): View
    {
        $this->authorize('manage', Organization::class);

        return view('admin.lgu-events.show', [
            'event' => $event->load(['announcement', 'createdBy:id,name']),
            'attending' => $event->attendance()->pluck('organization_id')->all(),
            // Credited when accredited, since lapsed: not on the checklist, and their credit stays.
            'lapsed' => Organization::whereIn('id', $event->attendance()->select('organization_id'))
                ->whereDoesntHave('accreditations', fn ($q) => $q->where('status', 'active'))->orderBy('name')->pluck('name'),
            // Organizations that can be credited: accredited ones (as for partner tags).
            'organizations' => Organization::whereHas('accreditations', fn ($q) => $q->where('status', 'active'))
                ->orderBy('name')->get(['id', 'name', 'sector', 'barangay']),
        ]);
    }

    public function update(Request $request, LguEvent $event): RedirectResponse
    {
        $this->authorize('manage', Organization::class);
        $credited = $event->attendance()->exists();
        $rules = $this->rules();
        if ($credited) {
            // Its attendance is recorded, so it has been held: the date can't move into the future.
            $rules['held_on'][] = 'before_or_equal:today';
        }
        $data = $request->validate($rules, $this->messages() + [
            'held_on.before_or_equal' => 'Attendance is already recorded for this event, so its date can\'t be in the future.',
        ]);

        $event->fill($this->details($data));
        $changed = $event->isDirty(['title', 'held_on', 'venue']);
        $event->save();
        $event->syncAttendance();
        AuditLog::record('lgu_event.updated', $event, ['title' => $event->title]);

        // An announced event whose name, date or venue changed is announced again, to the same audience.
        $resent = null;
        if ($changed && $event->announcement && ! $credited) {
            $resent = NoticeSender::send($request->user(), "LGU event changed: {$event->title}", $this->noticeBody($event),
                $event->announcement->audience, $event->announcement->audience_value, $event->announcement->emailed);
            $event->update(['notice_id' => $resent?->id ?? $event->notice_id]);
        }

        return back()->with('status', 'LGU event updated'
            .($credited ? ', and its attendance credits with it' : '')
            .($resent ? ". The change was announced to {$resent->recipients} ".str('organization')->plural($resent->recipients) : '').'.');
    }

    /** The organizations that attended: each is credited, or uncredited, to match the list ticked. */
    public function attendance(Request $request, LguEvent $event): RedirectResponse
    {
        $this->authorize('manage', Organization::class);
        abort_unless($event->isHeld(), 422, 'Attendance is recorded once the event has been held.');
        $ids = $request->validate([
            'organizations' => ['nullable', 'array', 'max:500'],
            'organizations.*' => ['integer', 'distinct'],
        ])['organizations'] ?? [];

        $changes = $event->recordAttendance($ids, $request->user());
        $attending = $event->attendance()->count();
        AuditLog::record('lgu_event.attendance', $event, ['added' => $changes['added'], 'removed' => $changes['removed'], 'attending' => $attending]);

        return back()->with('status', "Attendance saved: {$attending} ".str('organization')->plural($attending).' credited'
            .($changes['added'] || $changes['removed'] ? " ({$changes['added']} added, {$changes['removed']} removed)." : '.'));
    }

    public function destroy(LguEvent $event): RedirectResponse
    {
        $this->authorize('manage', Organization::class);
        $credited = $event->attendance()->pluck('organization_id')->all();
        AuditLog::record('lgu_event.deleted', $event, ['title' => $event->title, 'credits_removed' => count($credited)]);
        $event->delete();          // its attendance credits go with it (cascade)
        $event->rescore($credited);

        return redirect()->route('admin.lgu-events.index')->with('status', "“{$event->title}” was deleted".($credited ? ', with its attendance credits.' : '.'));
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'venue' => ['nullable', 'string', 'max:150'],
            'held_on' => ['required', 'date', 'after:2000-01-01', 'before:'.now()->addYears(2)->toDateString()],
        ];
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Name the event.',
            'description.required' => 'Say briefly what the event is.',
            'held_on.required' => 'Give the date of the event.',
        ];
    }

    private function details(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'venue' => $data['venue'] ?? null,
            'held_on' => $data['held_on'],
        ];
    }

    private function noticeBody(LguEvent $event): string
    {
        return "The LGU is holding {$event->title} on {$event->held_on->format('l, j F Y')}"
            .($event->venue ? " at {$event->venue}" : '').".\n\n{$event->description}\n\n"
            .'Attendance is recorded by the Desk Office, so you do not need to log this event as an activity.';
    }
}
