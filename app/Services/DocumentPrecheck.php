<?php

namespace App\Services;

/**
 * Judges the text the applicant's browser read from an upload (the PDF's own text, or Tesseract.js
 * reading a photo or scan) against what that requirement always shows (config/document_types.php
 * 'checks'). It "looks right" only when every check is found; otherwise the reviewer is told which
 * ones are missing. Very little text means the file couldn't be read, not that it is wrong.
 *
 * The verdict lives here, not in the browser, so it follows the same config the server validates
 * with. The text itself comes from the client and is therefore untrusted: the result is a hint for
 * PESO's reviewer and never blocks submission or approval (PRD §4.1).
 */
class DocumentPrecheck
{
    /** Fewer readable characters than this means the scan could not be read, not that it is wrong. */
    public const MIN_READABLE_CHARACTERS = 40;

    /**
     * @return array{status: string, details: ?array{checks: array<string, bool>, found: list<string>, organization_named: bool, characters: int}}
     */
    public static function evaluate(string $documentType, ?string $text, ?string $organizationName = null): array
    {
        if ($text === null || trim($text) === '') {
            return ['status' => 'not_checked', 'details' => null];
        }

        $haystack = self::normalize($text);
        $checks = [];
        $found = [];
        foreach (config("document_types.{$documentType}.checks", []) as $label => $words) {
            $hits = array_values(array_filter($words, fn (string $word) => str_contains($haystack, self::normalize($word))));
            $checks[$label] = $hits !== [];
            array_push($found, ...$hits);
        }

        $details = [
            'checks' => $checks,
            'found' => $found,
            'organization_named' => $organizationName !== null && str_contains($haystack, self::normalize($organizationName)),
            'characters' => mb_strlen($haystack),
        ];

        $status = match (true) {
            $details['characters'] < self::MIN_READABLE_CHARACTERS => 'unreadable',
            $checks !== [] && ! in_array(false, $checks, true) => 'matched',
            default => 'mismatch',
        };

        return ['status' => $status, 'details' => $details];
    }

    /** Lowercase letters and digits only, so OCR spacing and punctuation slips don't matter. */
    private static function normalize(string $text): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($text));
    }
}
