<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Carbon;

/**
 * Free checks on a logged activity, run on the server with no outside service (pre-final defense
 * panel: help the office verify many activities). Each problem becomes a plain sentence for the
 * reviewer; none blocks anything. With no problem the activity is "consistent" and can be verified in
 * bulk. The AI reading (SheetReader), when switched on, adds its own findings next to these.
 */
class SheetChecks
{
    /** Logged this long after the activity, it's worth a look. */
    public const LATE_AFTER_DAYS = 60;

    /** Fewer characters than this and the sheet's text was too little to judge (handwriting). */
    private const MIN_TEXT = 40;

    /** @param  string|null  $text  what the browser read from the sheet (typed sheets); never stored */
    public static function run(Activity $activity, ?string $text = null): void
    {
        $findings = [];

        if (! $activity->evidence_path) {
            $findings[] = 'No sign-in sheet is attached.';
        } else {
            $same = $activity->evidence_hash
                ? Activity::with('organization:id,name')->where('evidence_hash', $activity->evidence_hash)->whereKeyNot($activity->id)->first()
                : null;
            if ($same) {
                $findings[] = "The same file is attached to “{$same->title}” ({$same->organization->name}, {$same->activity_date->format('d M Y')}).";
            }

            if ($text !== null && strlen(self::normalize($text)) >= self::MIN_TEXT
                && ! self::mentionsDate($text, $activity->activity_date) && ! self::mentionsTitle($text, $activity->title)) {
                $findings[] = "The sheet doesn't show the activity's date or title.";
            }
        }

        $late = (int) $activity->activity_date->diffInDays($activity->created_at ?? now());
        if ($late > self::LATE_AFTER_DAYS) {
            $findings[] = "Logged {$late} days after the activity.";
        }

        $checks = $activity->check_findings ?? [];
        $checks['free'] = $findings;
        $activity->forceFill(['check_findings' => $checks, 'checked_at' => now()]);
        self::settle($activity);
    }

    /** consistent or flagged from every finding so far (a failed AI reading keeps its own status). */
    public static function settle(Activity $activity): void
    {
        $all = array_merge(...array_values($activity->check_findings ?? []));
        $activity->check_status = $all ? 'flagged' : 'consistent';
        $activity->save();
    }

    /** Every finding, free and AI, as one list. */
    public static function findings(Activity $activity): array
    {
        return array_merge(...array_values($activity->check_findings ?? ['free' => []]));
    }

    public static function normalize(string $text): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($text));
    }

    /** The date as people write it: 18 October 2026, October 18, 2026, Oct 18 2026, 10/18/2026, 2026-10-18. */
    public static function mentionsDate(string $text, Carbon $date): bool
    {
        $haystack = self::normalize($text);
        foreach (['jFY', 'dFY', 'FjY', 'FdY', 'jMY', 'MjY', 'mdY', 'ndY', 'njY', 'Ymd'] as $format) {
            if (str_contains($haystack, self::normalize($date->format($format)))) {
                return true;
            }
        }

        return false;
    }

    /** At least half of the title's longer words appear in the text. */
    public static function mentionsTitle(string $text, string $title): bool
    {
        $words = collect(preg_split('/\W+/u', mb_strtolower($title)))->filter(fn ($w) => mb_strlen($w) >= 4)->unique();
        if ($words->isEmpty()) {
            return false;
        }
        $haystack = mb_strtolower($text);

        return $words->filter(fn ($w) => str_contains($haystack, $w))->count() * 2 >= $words->count();
    }
}
