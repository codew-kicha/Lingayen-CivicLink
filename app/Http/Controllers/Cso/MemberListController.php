<?php

namespace App\Http\Controllers\Cso;

use App\Exports\ReportSheet;
use App\Http\Controllers\Controller;
use App\Models\OrganizationMember;
use App\Rules\PersonName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The member list as a spreadsheet (pre-final defense panel): a template to fill in, and an upload
 * that is read into the member table on the page for review. Nothing is saved here; the person still
 * checks the rows and saves the form.
 */
class MemberListController extends Controller
{
    public const HEADINGS = ['Last name', 'First name', 'Middle name', 'Position', 'Contact number', 'Email'];

    public function template(): BinaryFileResponse
    {
        return Excel::download(new ReportSheet('Members', self::HEADINGS, [
            ['Dela Cruz', 'Juan', 'Santos', OrganizationMember::REPRESENTATIVE, '0917 123 4567', 'juan@gmail.com'],
            ['Reyes', 'Maria', '', 'Secretary', '', ''],
        ]), 'member-list-template.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:2048']]);

        $sheet = Excel::toArray(new class implements WithHeadingRow {}, $request->file('file'))[0] ?? [];
        $rows = [];
        $skipped = [];

        foreach ($sheet as $i => $line) {
            $row = [
                'last_name' => trim((string) ($line['last_name'] ?? '')),
                'first_name' => trim((string) ($line['first_name'] ?? '')),
                'middle_name' => trim((string) ($line['middle_name'] ?? '')),
                'position' => trim((string) ($line['position'] ?? '')),
                'contact_number' => trim((string) ($line['contact_number'] ?? '')),
                'email' => trim((string) ($line['email'] ?? '')),
            ];
            if (! array_filter($row)) {
                continue; // an empty line
            }
            if ($row['last_name'] === '' || $row['first_name'] === '') {
                $skipped[] = 'Row '.($i + 2).': the last name and first name are needed.';

                continue;
            }
            if (! PersonName::valid($row['last_name']) || ! PersonName::valid($row['first_name']) || ! PersonName::valid($row['middle_name'])) {
                $skipped[] = 'Row '.($i + 2).': the name has numbers or symbols ('.trim("{$row['first_name']} {$row['last_name']}").').';

                continue;
            }
            $rows[] = $row;
        }

        if (! $rows && ! $skipped) {
            return response()->json(['message' => 'No members were found. Use the template: the first row holds the column names.'], 422);
        }

        return response()->json(['rows' => array_slice($rows, 0, 300), 'skipped' => $skipped]);
    }
}
