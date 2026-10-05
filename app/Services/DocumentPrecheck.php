<?php

namespace App\Services;

/**
 * Judges the text the applicant's browser read from an upload (Tesseract.js, or the PDF's own text
 * layer) against the words expected in that requirement (config/document_types.php).
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
     * @return array{status: string, details: ?array{found: list<string>, expected: int, organization_named: bool, characters: int}}
     */
    public static function evaluate(string $documentType, ?string $text, ?string $organizationName = null): array
    {
        if ($text === null || trim($text) === '') {
            return ['status' => 'not_checked', 'details' => null];
        }

        $haystack = self::normalize($text);
        $type = config("document_types.{$documentType}", []);
        $found = array_values(array_filter(
            $type['keywords'] ?? [],
            fn (string $keyword) => str_contains($haystack, self::normalize($keyword)),
        ));

        $details = [
            'found' => $found,
            'expected' => $type['min_matches'] ?? 1,
            'organization_named' => $organizationName !== null && str_contains($haystack, self::normalize($organizationName)),
            'characters' => mb_strlen($haystack),
        ];

        $status = match (true) {
            $details['characters'] < self::MIN_READABLE_CHARACTERS => 'unreadable',
            count($found) >= $details['expected'] => 'matched',
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
