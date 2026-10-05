<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Loads the office's existing organization list (PRD §19: 202 associations) from a CSV with a
 * header row of name, sector, barangay, and optionally advocacy. Other columns are ignored.
 *
 * All or nothing: any invalid row aborts the whole import, so a half-loaded list never needs
 * cleaning up. Names already on file (or repeated in the file) are skipped, which makes a
 * re-run of the same file safe. Imported organizations have no login until one is attached.
 */
class OrganizationImporter
{
    public const COLUMNS = ['name', 'sector', 'barangay', 'advocacy'];

    /**
     * @return array{created: int, skipped: list<string>, errors: array<int, string>}
     */
    public function import(string $path, bool $dryRun = false): array
    {
        $result = ['created' => 0, 'skipped' => [], 'errors' => []];
        $rows = $this->read($path, $result['errors']);

        if ($result['errors']) {
            return $result;
        }

        $existing = Organization::pluck('name')->map(fn ($name) => mb_strtolower(trim($name)))->flip();
        $valid = [];

        foreach ($rows as $line => $row) {
            $key = mb_strtolower($row['name']);

            if ($row['name'] === '') {
                $result['errors'][$line] = 'Name is empty.';
            } elseif (mb_strlen($row['name']) > 255) {
                $result['errors'][$line] = 'Name is longer than 255 characters.';
            } elseif ($existing->has($key)) {
                $result['skipped'][] = $row['name'];
            } elseif (! $sector = $this->match($row['sector'], config('sectors'))) {
                $result['errors'][$line] = "Sector \"{$row['sector']}\" is not one of the office's sectors.";
            } elseif (! $barangay = $this->match(preg_replace('/^(brgy\.?|barangay)\s+/i', '', $row['barangay']), config('barangays'))) {
                $result['errors'][$line] = "Barangay \"{$row['barangay']}\" is not a Lingayen barangay.";
            } else {
                $existing->put($key, true);
                $valid[] = ['name' => $row['name'], 'sector' => $sector, 'barangay' => $barangay, 'advocacy' => $row['advocacy'] ?: null];
            }
        }

        if ($result['errors'] || $dryRun) {
            $result['created'] = $result['errors'] ? 0 : count($valid);

            return $result;
        }

        DB::transaction(function () use ($valid) {
            foreach ($valid as $attributes) {
                Organization::create($attributes + ['user_id' => null]);
            }

            AuditLog::record('organizations.imported', null, ['count' => count($valid)]);
        });

        $result['created'] = count($valid);

        return $result;
    }

    /** @return array<int, array<string, string>> rows keyed by their line number in the file */
    private function read(string $path, array &$errors): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            $errors[1] = 'The file is empty.';

            return [];
        }

        // Excel saves CSVs with a byte-order mark; strip it so the first column name still matches.
        $header = array_map(fn ($h) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);

        if ($missing = array_diff(['name', 'sector', 'barangay'], $header)) {
            $errors[1] = 'Missing column: '.implode(', ', $missing).'. The first row must name the columns.';

            return [];
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if ($values === [null] || ! array_filter($values, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }

            $row = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
            $rows[$line] = array_map(fn ($v) => trim((string) $v), array_intersect_key($row + array_fill_keys(self::COLUMNS, ''), array_flip(self::COLUMNS)));
        }

        fclose($handle);

        return $rows;
    }

    /** Case-insensitive lookup that returns the canonical spelling. */
    private function match(string $value, array $options): ?string
    {
        foreach ($options as $option) {
            if (mb_strtolower($option) === mb_strtolower(trim($value))) {
                return $option;
            }
        }

        return null;
    }
}
