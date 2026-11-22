<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Notifications\ActivityVerified;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * An activity the LGU organized (pre-final defense panel): the Desk Office creates it, may announce
 * it as a notice, and after it is held records which organizations attended. Each attending
 * organization gets an ordinary verified activity linked to the event (its attendance credit), counted
 * as LGU-organized, with no residents reached of its own: the event's crowd is the LGU's.
 */
class LguEvent extends Model
{
    protected $fillable = ['title', 'description', 'venue', 'held_on', 'notice_id', 'created_by', 'attendance_recorded_at'];

    protected function casts(): array
    {
        return [
            'held_on' => DateOnly::class,
            'attendance_recorded_at' => 'datetime',
        ];
    }

    /** Attendance is recorded once the event has started (from its day on). */
    public function isHeld(): bool
    {
        return ! $this->held_on->isFuture();
    }

    /**
     * Matches the attendance to the checklist the office saved: ticked accredited organizations are
     * credited (a verified LGU-organized activity, and an in-app notice), unticked ones lose their
     * credit. The checklist lists only accredited organizations, so one credited earlier whose
     * accreditation has since lapsed is never on it and keeps its credit. Each change is rescored.
     *
     * @param  array<int, int|string>  $organizationIds
     * @return array{added: int, removed: int, added_ids: list<int>}
     */
    public function recordAttendance(array $organizationIds, User $by): array
    {
        $accredited = Organization::whereHas('accreditations', fn ($q) => $q->where('status', 'active'))->pluck('id');
        $wanted = $accredited->intersect(array_map('intval', $organizationIds))->values();

        [$added, $removed] = DB::transaction(function () use ($wanted, $accredited, $by) {
            $current = $this->attendance()->pluck('organization_id');
            // Only organizations that were on the checklist (accredited) can have been unticked.
            $removed = $current->intersect($accredited)->diff($wanted)->values();
            $this->attendance()->whereIn('organization_id', $removed)->delete();

            $added = $wanted->diff($current)->values()->map(fn ($organizationId) => $this->attendance()->create($this->creditAttributes() + [
                'organization_id' => $organizationId,
                'logged_by' => $by->id,
                'verified_by' => $by->id,
            ]));
            $this->update(['attendance_recorded_at' => now()]);

            return [$added, $removed];
        });

        $this->rescore($added->pluck('organization_id')->merge($removed)->all());
        // Each newly credited organization is told in the app (not by email: one event can credit many).
        Activity::with('organization.user')->whereKey($added->pluck('id'))->get()->each(function (Activity $credit) {
            if ($credit->organization?->user) {
                Notification::send($credit->organization->user, new ActivityVerified($credit, ['database']));
            }
        });

        return ['added' => $added->count(), 'removed' => $removed->count(), 'added_ids' => $added->pluck('organization_id')->all()];
    }

    /**
     * After the event is edited, its attendance credits show the same title, date and venue, and the
     * credited organizations are rescored (a new date can move a credit into or out of a score period).
     */
    public function syncAttendance(): void
    {
        $this->attendance()->update(collect($this->creditAttributes())->only(['title', 'description', 'venue', 'activity_date'])->all());
        $this->rescore($this->attendance()->pluck('organization_id')->all());
    }

    /** Recalculates the score of each of these organizations. */
    public function rescore(array $organizationIds): void
    {
        $scores = app(PerformanceScoreCalculator::class);
        Organization::whereKey(array_unique($organizationIds))->get()->each(fn (Organization $organization) => $scores->recalculate($organization));
    }

    /** What an attendance credit records: the event's details, LGU-organized, verified, no reach. */
    private function creditAttributes(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'venue' => $this->venue,
            'activity_date' => $this->held_on->toDateString(),
            'participants_estimate' => null,
            'activity_source' => 'lgu_organized',
            'status' => 'verified',
            'verified_at' => now(),
        ];
    }

    /** The organizations credited with attending, through their attendance activities. */
    public function attendance(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(OfficeNotice::class, 'notice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
