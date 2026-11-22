<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityPhoto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Automated verification of a logged activity's evidence (pre-final defense panel, requirement 6),
 * run on the server with no outside service: the sign-in sheet, up to three photos and the venue.
 *
 * - The same file, or the same picture resized or re-saved (ImageForensics::fingerprint), attached
 *   to another activity.
 * - The photos' camera data: taken on the activity date, edited in an editing app, taken in Lingayen.
 * - A typed sheet's text against the activity's date, title and venue (the text is never stored).
 * - How long after the activity it was logged.
 *
 * Each finding carries points off a 0-100 confidence score; a high-weight finding always flags. The
 * result is Verified (consistent), Needs review or Flagged, with the reasons both ways. The AI
 * reading (SheetReader), when switched on, adds its own findings. Decision support for the office:
 * nothing is decided automatically, and the office only needs to open the ones not Verified.
 */
class SheetChecks
{
    /** Logged this long after the activity, it's worth a look. */
    public const LATE_AFTER_DAYS = 60;

    /** At or above this score (and nothing high-weight), the result is Verified. */
    public const VERIFIED_FROM = 80;

    /** Below this score the result is Flagged even without a high-weight finding. */
    public const FLAGGED_BELOW = 50;

    /** A photo taken more than this many days from the activity date is worth a look. */
    public const PHOTO_DAYS = 2;

    /** Fewer characters than this and the sheet's text was too little to judge (handwriting). */
    private const MIN_TEXT = 40;

    /** @param  string|null  $text  what the browser read from the sheet (typed sheets); never stored */
    public static function run(Activity $activity, ?string $text = null): void
    {
        $problems = [];
        $ok = [];
        $add = function (string $level, int $points, string $finding) use (&$problems) {
            $problems[] = ['text' => $finding, 'level' => $level, 'points' => $points];
        };
        $readable = $text !== null && strlen(self::normalize($text)) >= self::MIN_TEXT;
        $others = self::otherImages($activity);

        // The sign-in sheet.
        if (! $activity->evidence_path) {
            $add('high', 40, 'No sign-in sheet is attached.');
        } else {
            $same = $activity->evidence_hash ? $others->firstWhere('hash', $activity->evidence_hash) : null;
            $alike = ! $same && $activity->evidence_phash ? self::closest($others, $activity->evidence_phash) : null;
            if ($same) {
                $add('high', 60, "The same file is attached to {$same['owner']}.");
            } elseif ($alike) {
                $add('high', 50, "The sign-in sheet looks like a picture already attached to {$alike['owner']}.");
            } else {
                $ok[] = 'The sign-in sheet is not attached to any other activity.';
            }

            if ($readable) {
                if (! self::mentionsDate($text, $activity->activity_date) && ! self::mentionsTitle($text, $activity->title)) {
                    $add('medium', 20, "The sheet doesn't show the activity's date or title.");
                } else {
                    $ok[] = "The sheet shows the activity's date or title.";
                }
                if (filled($activity->venue) && self::mentionsTitle($text, $activity->venue)) {
                    $ok[] = 'The sheet mentions the venue.';
                } elseif (filled($activity->venue)) {
                    $add('low', 10, "The sheet doesn't mention the venue ({$activity->venue}).");
                }
            }
        }

        // The photos.
        $photos = $activity->photos()->get();
        if ($photos->isEmpty()) {
            $add('low', 10, 'No activity photos are attached.');
        }
        foreach ($photos->values() as $i => $photo) {
            self::checkPhoto($activity, $photo, 'Photo '.($i + 1), $others, $add, $ok);
        }

        // Timing.
        $late = (int) $activity->activity_date->diffInDays($activity->created_at ?? now());
        if ($late > self::LATE_AFTER_DAYS) {
            $add('low', 15, "Logged {$late} days after the activity.");
        }

        $checks = $activity->check_findings ?? [];
        $checks['free'] = $problems;
        $checks['ok'] = $ok;
        $activity->forceFill(['check_findings' => $checks, 'checked_at' => now()]);
        self::settle($activity);
    }

    /** The score and the result from every finding so far, free and AI. */
    public static function settle(Activity $activity): void
    {
        $problems = self::problems($activity);
        $score = max(0, 100 - array_sum(array_column($problems, 'points')));
        $high = in_array('high', array_column($problems, 'level'), true);

        $activity->check_score = $score;
        $activity->check_status = match (true) {
            $high || $score < self::FLAGGED_BELOW => 'flagged',
            $score < self::VERIFIED_FROM => 'review',
            default => 'consistent',
        };
        $activity->save();
    }

    /**
     * Every finding with its weight, the heaviest first. Findings saved before the score existed
     * (plain sentences) count as medium.
     *
     * @return list<array{text: string, level: string, points: int}>
     */
    public static function problems(Activity $activity): array
    {
        $checks = $activity->check_findings ?? [];
        $all = array_map(
            fn ($finding) => is_array($finding) ? $finding : ['text' => (string) $finding, 'level' => 'medium', 'points' => 20],
            array_merge($checks['free'] ?? [], $checks['ai'] ?? []),
        );
        usort($all, fn ($a, $b) => $b['points'] <=> $a['points']);

        return $all;
    }

    /** Every finding (what to look at), as sentences, the heaviest first. */
    public static function findings(Activity $activity): array
    {
        return array_column(self::problems($activity), 'text');
    }

    /** What the checks confirmed, as sentences. */
    public static function confirmations(Activity $activity): array
    {
        $checks = $activity->check_findings ?? [];

        return array_merge($checks['ok'] ?? [], $checks['ai_ok'] ?? []);
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

    /** One photo: reused, edited, taken on another day, taken elsewhere, or with no camera data. */
    private static function checkPhoto(Activity $activity, ActivityPhoto $photo, string $name, $others, \Closure $add, array &$ok): void
    {
        $same = $others->firstWhere('hash', $photo->hash);
        $alike = ! $same && $photo->phash ? self::closest($others, $photo->phash) : null;
        if ($same) {
            $add('high', 50, "{$name} is the same file as one attached to {$same['owner']}.");
        } elseif ($alike) {
            $add('high', 50, "{$name} looks like a picture already attached to {$alike['owner']}.");
        }

        $meta = $photo->metadata ?? [];
        if ($editor = ImageForensics::editor($meta['software'] ?? null)) {
            $add('medium', 25, "{$name} was edited in {$editor}.");
        }

        if ($taken = $photo->takenAt()) {
            $days = (int) abs($taken->copy()->startOfDay()->diffInDays($activity->activity_date->copy()->startOfDay(), false));
            if ($days > self::PHOTO_DAYS) {
                $add('medium', 20, "{$name} was taken on {$taken->format('d M Y')}, {$days} days from the activity date.");
            } else {
                $ok[] = "{$name} was taken ".($days === 0 ? 'on the activity date' : "within {$days} ".str('day')->plural($days).' of the activity date').'.';
            }
        }

        if (isset($meta['lat'], $meta['lng'])) {
            $km = ImageForensics::kmFromLingayen($meta['lat'], $meta['lng']);
            if ($km > ImageForensics::NEAR_KM) {
                $add('medium', 15, "{$name} was taken ".number_format($km).' km from Lingayen.');
            } else {
                $ok[] = "{$name} was taken in Lingayen (GPS).";
            }
        }

        if (! $taken && ! isset($meta['lat'])) {
            $add('low', 5, "{$name} has no camera data (date or location), as when a photo is sent through a chat app.");
        }
    }

    /**
     * The sheets and photos of every other activity: exact hash, fingerprint, and who owns it.
     *
     * @return Collection<int, array{hash: ?string, phash: ?string, owner: string}>
     */
    private static function otherImages(Activity $activity)
    {
        $owner = fn (Activity $a) => "“{$a->title}” ({$a->organization?->name}, {$a->activity_date->format('d M Y')})";

        $sheets = Activity::with('organization:id,name')->whereKeyNot($activity->id)->whereNotNull('evidence_path')
            ->get(['id', 'organization_id', 'title', 'activity_date', 'evidence_hash', 'evidence_phash'])
            ->map(fn (Activity $a) => ['hash' => $a->evidence_hash, 'phash' => $a->evidence_phash, 'owner' => $owner($a)]);

        $photos = ActivityPhoto::with('activity.organization:id,name')->where('activity_id', '!=', $activity->id)
            ->get(['id', 'activity_id', 'hash', 'phash'])
            ->filter(fn (ActivityPhoto $p) => $p->activity)
            ->map(fn (ActivityPhoto $p) => ['hash' => $p->hash, 'phash' => $p->phash, 'owner' => $owner($p->activity)]);

        return $sheets->concat($photos)->values();
    }

    /** The other image whose fingerprint is closest to this one, if it is the same picture. */
    private static function closest($others, string $phash): ?array
    {
        return $others->filter(fn ($o) => $o['phash'])
            ->map(fn ($o) => $o + ['distance' => ImageForensics::distance($phash, $o['phash'])])
            ->filter(fn ($o) => $o['distance'] <= ImageForensics::SAME_PICTURE)
            ->sortBy('distance')->first();
    }
}
