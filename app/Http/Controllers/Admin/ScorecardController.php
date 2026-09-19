<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScorecardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('admin.scorecards.index', [
            'organizations' => Organization::with(['performanceScores' => fn ($q) => $q->latest('computed_at')->limit(1)])
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function recalculate(PerformanceScoreCalculator $calculator): RedirectResponse
    {
        $this->authorize('viewAny', Organization::class);

        Organization::each(fn (Organization $organization) => $calculator->recalculate($organization));

        return back()->with('status', 'Scores recalculated for every organization.');
    }
}
