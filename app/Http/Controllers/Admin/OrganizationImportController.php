<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\OrganizationImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationImportController extends Controller
{
    public function create(): View
    {
        $this->authorize('manage', Organization::class);

        return view('admin.organizations.import');
    }

    public function store(Request $request, OrganizationImporter $importer): RedirectResponse
    {
        $this->authorize('manage', Organization::class);

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $importer->import($request->file('file')->getRealPath());

        if ($result['errors']) {
            return back()->with('importErrors', $result['errors']);
        }

        return redirect()->route('admin.organizations.index', ['account' => 'none'])->with('status',
            "Imported {$result['created']} organizations.".($result['skipped'] ? ' Skipped '.count($result['skipped']).' already on file.' : ''));
    }

    public function template(): StreamedResponse
    {
        $this->authorize('manage', Organization::class);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, OrganizationImporter::COLUMNS);
            fputcsv($out, ['Example Farmers Association', config('sectors')[0], array_values(config('barangays'))[0], 'Optional one-line description']);
            fclose($out);
        }, 'organizations-template.csv', ['Content-Type' => 'text/csv']);
    }
}
