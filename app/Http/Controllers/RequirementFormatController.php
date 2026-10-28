<?php

namespace App\Http\Controllers;

use App\Exports\ReportSheet;
use App\Models\AuditLog;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Formats for the requirements an organization prepares itself (config/document_types.php,
 * 'formats'): the officers and members list (PDF, Excel) and a sample constitution and by-laws
 * (PDF). They are filled in from the organization's profile where it has one: a representative's
 * own organization, or, for the Desk Office encoding a paper submission, ?organization={id}.
 */
class RequirementFormatController extends Controller
{
    /** Blank rows after the saved officers, for members to be written in by hand. */
    private const BLANK_ROWS = 10;

    public function show(Request $request, string $document, string $format): Response
    {
        abort_unless(in_array($format, config("document_types.{$document}.formats", []), true), 404);

        $organization = $this->organization($request);
        $members = $organization?->members()->orderBy('id')->get() ?? collect();
        $slug = Str::slug($organization?->name ?? 'organization');

        if ($document === 'officers_members_list' && $format === 'xlsx') {
            $rows = $members->values()->map(fn ($m, $i) => [$i + 1, $m->name, $m->position, $m->contact_number, $m->email, ''])->all();
            foreach (range(1, self::BLANK_ROWS) as $n) {
                $rows[] = [count($rows) + 1, '', '', '', '', ''];
            }

            return Excel::download(
                new ReportSheet('Officers and members', ['No.', 'Full name', 'Position', 'Contact number', 'Email', 'Signature'], $rows),
                "officers-and-members-{$slug}.xlsx",
            );
        }

        $view = $document === 'officers_members_list' ? 'pdf.requirement-officers' : 'pdf.requirement-constitution';

        return Pdf::setOption(['isFontSubsettingEnabled' => true])
            ->loadView($view, ['organization' => $organization, 'members' => $members, 'blankRows' => self::BLANK_ROWS])
            ->download(($document === 'officers_members_list' ? 'officers-and-members-' : 'constitution-and-by-laws-')."{$slug}.pdf");
    }

    // A representative always gets their own organization, whatever the URL says; office staff may
    // name one (it holds member contact details, so that is audited like the members printout).
    private function organization(Request $request): ?Organization
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return $user->organization;
        }

        $organization = $request->integer('organization') ? Organization::find($request->integer('organization')) : null;
        if ($organization) {
            AuditLog::record('organization.members_printed', $organization, ['format' => 'requirement template']);
        }

        return $organization;
    }
}
