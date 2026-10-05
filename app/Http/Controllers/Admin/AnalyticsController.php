<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AnalyticsWorkbook;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\ReportData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page, PDF, and Excel all read one ReportData for one period (?from=&to=), so the exports always
 * match what PESO saw on screen.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('admin.analytics', ReportData::fromRequest($request)->all());
    }

    public function export(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);

        $report = ReportData::fromRequest($request);

        return Pdf::loadView('admin.analytics-pdf', [
            ...$report->all(),
            'awards' => $report->awardsRanking(),
            'generatedAt' => now(),
            'generatedBy' => $request->user()->name,
        ])->download($this->filename($report, 'pdf'));
    }

    public function excel(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);

        $report = ReportData::fromRequest($request);

        return Excel::download(new AnalyticsWorkbook($report), $this->filename($report, 'xlsx'));
    }

    public function awards(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);

        $report = ReportData::fromRequest($request);

        return Excel::download(AnalyticsWorkbook::awards($report), 'civiclink-awards-ranking-'.$report->to->format('Y-m-d').'.xlsx');
    }

    private function filename(ReportData $report, string $extension): string
    {
        return "civiclink-report-{$report->from->format('Y-m-d')}-to-{$report->to->format('Y-m-d')}.{$extension}";
    }
}
