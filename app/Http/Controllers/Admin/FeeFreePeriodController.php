<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FeeFreePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The free filing periods (client, Nov 2026): after each new LGU administration a DILG memorandum
 * opens about three months in which accreditation is free. The Desk Office records each one here;
 * applications submitted inside it need no receipt and are accredited for three years.
 */
class FeeFreePeriodController extends Controller
{
    public function index(): View
    {
        return view('admin.fee-periods.index', [
            'periods' => FeeFreePeriod::with('createdBy:id,name')->orderByDesc('starts_on')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $period = FeeFreePeriod::create($this->validated($request) + ['created_by' => $request->user()->id]);
        AuditLog::record('fee_period.created', $period, ['period_id' => $period->id, 'memorandum' => $period->memorandum]);

        return back()->with('status', "Free filing period saved: {$period->dates()}.");
    }

    public function update(Request $request, FeeFreePeriod $period): RedirectResponse
    {
        $period->update($this->validated($request));
        AuditLog::record('fee_period.updated', $period, ['period_id' => $period->id, 'memorandum' => $period->memorandum]);

        return back()->with('status', "Free filing period updated: {$period->dates()}.");
    }

    public function destroy(FeeFreePeriod $period): RedirectResponse
    {
        AuditLog::record('fee_period.deleted', $period, ['period_id' => $period->id, 'memorandum' => $period->memorandum]);
        $period->delete();

        return back()->with('status', 'Free filing period removed. Applications already filed keep their fee decision.');
    }

    /** @return array{memorandum: string, starts_on: string, ends_on: string} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'memorandum' => ['required', 'string', 'max:120'],
            'starts_on' => ['required', 'date', 'after:2000-01-01'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on', 'before:2100-01-01'],
        ], [
            'memorandum.required' => 'Name the memorandum that opened the period, e.g. DILG MC No. 2025-060.',
            'ends_on.after_or_equal' => 'The period must end on or after the day it starts.',
        ]);
    }
}
