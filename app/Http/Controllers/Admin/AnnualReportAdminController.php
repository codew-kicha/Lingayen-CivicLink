<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnualReportRequest;
use App\Models\AnnualReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AnnualReportAdminController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AnnualReport::class);

        return view('admin.annual-reports.index', [
            'reports' => AnnualReport::with('uploadedBy')->orderByDesc('year')->get(),
        ]);
    }

    public function store(StoreAnnualReportRequest $request): RedirectResponse
    {
        AnnualReport::create([
            ...$request->safe()->only(['title', 'year']),
            'file_path' => $request->file('file')->store('annual-reports'),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Annual report published to the Resources page.');
    }

    public function update(StoreAnnualReportRequest $request, AnnualReport $annualReport): RedirectResponse
    {
        $data = $request->safe()->only(['title', 'year']);

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($annualReport->file_path);
            $data['file_path'] = $request->file('file')->store('annual-reports');
        }

        $annualReport->update($data);

        return back()->with('status', 'Annual report updated.');
    }

    public function destroy(AnnualReport $annualReport): RedirectResponse
    {
        $this->authorize('delete', $annualReport);

        Storage::disk('local')->delete($annualReport->file_path);
        $annualReport->delete();

        return back()->with('status', 'Annual report removed.');
    }
}
