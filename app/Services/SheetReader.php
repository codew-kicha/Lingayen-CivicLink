<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\OutputConfig;
use App\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * The AI layer of activity verification (pre-final defense panel, "emerging technology"): Claude
 * Haiku reads the sign-in sheet's photo or PDF and reports the date written on it, its title and how
 * many people signed. Those are compared with what the organization logged, and any difference is a
 * finding next to the free checks (SheetChecks). Advice for the reviewer only; it decides nothing.
 *
 * Off unless ANTHROPIC_API_KEY is set and AI_SHEET_READER=true (config/services.php). The sheet holds
 * residents' names, so switching it on needs the office's Data Privacy Act approval
 * (docs/CLIENT_CONFIRMATIONS.md, item 4). A failed reading leaves the activity to the free checks and
 * can be retried from Verify activities.
 */
class SheetReader
{
    /** Tests replace the API call: fn (Activity) => ['readable' => ..., 'sheet_date' => ..., ...]. */
    public static ?\Closure $fake = null;

    /** Images and PDFs the model can read. */
    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public static function enabled(): bool
    {
        return (bool) config('services.anthropic.sheet_reader') && (filled(config('services.anthropic.key')) || self::$fake);
    }

    /** Reads one activity's sheet and records the findings. Returns false when it couldn't. */
    public static function read(?Activity $activity): bool
    {
        if (! $activity || ! $activity->evidence_path || ! self::enabled()) {
            return false;
        }

        try {
            $result = self::$fake ? (self::$fake)($activity) : self::ask($activity);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
        if ($result === null) {
            return false;
        }

        $findings = [];
        $names = max(0, (int) ($result['names_count'] ?? 0));

        if (empty($result['readable'])) {
            $findings[] = 'The AI could not read the sign-in sheet.';
        } else {
            $reached = (int) $activity->participants_estimate;
            if ($reached > 0 && $names * 2 < $reached) {
                $findings[] = "The sheet lists {$names} ".str('name')->plural($names)."; the activity reports {$reached} residents reached.";
            }
            $written = self::date($result['sheet_date'] ?? null);
            if ($written && abs($written->diffInDays($activity->activity_date)) > 3) {
                $findings[] = "The sheet is dated {$written->format('d M Y')}; the activity was logged for {$activity->activity_date->format('d M Y')}.";
            }
        }

        $checks = $activity->check_findings ?? [];
        $checks['ai'] = $findings;
        $activity->forceFill([
            'check_findings' => $checks,
            'ai_names_count' => empty($result['readable']) ? null : $names,
            'ai_checked_at' => now(),
        ]);
        SheetChecks::settle($activity);

        return true;
    }

    /** One Messages API call with the sheet attached; null when the file type can't be sent. */
    private static function ask(Activity $activity): ?array
    {
        $mime = (string) $activity->evidence_mime;
        $data = base64_encode(Storage::disk('local')->get($activity->evidence_path));
        $file = match (true) {
            in_array($mime, self::IMAGE_TYPES, true) => ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $mime, 'data' => $data]],
            $mime === 'application/pdf' => ['type' => 'document', 'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => $data]],
            default => null,
        };
        if (! $file) {
            return null;
        }

        $client = new Client(apiKey: config('services.anthropic.key'));
        $message = $client->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 2048,
            messages: [['role' => 'user', 'content' => [
                $file,
                ['type' => 'text', 'text' => "This is the attendance or sign-in sheet for a community activity of a civil society organization in Lingayen, Pangasinan, Philippines. Read it and report:\n"
                    ."- readable: whether you could make out the sheet at all\n"
                    ."- sheet_date: the date written on the sheet as YYYY-MM-DD, or null if there is none\n"
                    ."- sheet_title: the activity's name or title written on the sheet, or null\n"
                    ."- names_count: how many different people are listed or signed (count filled rows only, not blank lines)\n"
                    .'Report only what the sheet shows; do not guess.'],
            ]]],
            outputConfig: OutputConfig::with(effort: 'low', format: JSONOutputFormat::with(schema: [
                'type' => 'object',
                'properties' => [
                    'readable' => ['type' => 'boolean'],
                    'sheet_date' => ['type' => ['string', 'null']],
                    'sheet_title' => ['type' => ['string', 'null']],
                    'names_count' => ['type' => 'integer'],
                ],
                'required' => ['readable', 'sheet_date', 'sheet_title', 'names_count'],
                'additionalProperties' => false,
            ])),
            requestOptions: ['timeout' => 45, 'maxRetries' => 1],
        );

        if ($message->stopReason === 'refusal') {
            return null;
        }
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return json_decode($block->text, true) ?: null;
            }
        }

        return null;
    }

    private static function date(?string $value): ?Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
