<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('admin.analytics', $this->data());
    }

    public function export(): Response
    {
        $this->authorize('viewAny', Organization::class);

        return Pdf::loadView('admin.analytics-pdf', [
            ...$this->data(),
            'generatedAt' => now(),
            'generatedBy' => request()->user()->name,
        ])->download('civiclink-report-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * The four charts required by PRD §6.1: sector distribution, activity volume trend,
     * top contributors, and compliance trend.
     *
     * @return array<string, mixed>
     */
    private function data(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $ago) => Carbon::now()->subMonths($ago)->startOfMonth());

        return [
            'summary' => [
                'organizations' => Organization::count(),
                'accredited' => Accreditation::where('status', 'active')->count(),
                'verifiedActivities' => Activity::where('status', 'verified')->count(),
                'residentsReached' => (int) Activity::where('status', 'verified')->sum('participants_estimate'),
            ],

            'sectorDistribution' => Organization::query()
                ->whereHas('accreditations', fn ($q) => $q->where('status', 'active'))
                ->selectRaw('sector, count(*) as total')
                ->groupBy('sector')
                ->orderByDesc('total')
                ->pluck('total', 'sector'),

            'activityTrend' => $months->map(fn (Carbon $month) => [
                'label' => $month->format('M Y'),
                'total' => Activity::where('status', 'verified')
                    ->whereBetween('activity_date', [$month, $month->copy()->endOfMonth()])
                    ->count(),
            ]),

            'topContributors' => Organization::query()
                ->whereHas('activities', fn ($q) => $q->where('status', 'verified'))
                ->withCount(['activities as verified_count' => fn ($q) => $q->where('status', 'verified')])
                ->withSum(['activities as reach' => fn ($q) => $q->where('status', 'verified')], 'participants_estimate')
                ->orderByDesc('verified_count')
                ->limit(10)
                ->get(),

            'complianceTrend' => $months->map(fn (Carbon $month) => [
                'label' => $month->format('M Y'),
                'approved' => ApplicationModel::where('status', 'approved')
                    ->whereBetween('reviewed_at', [$month, $month->copy()->endOfMonth()])
                    ->count(),
                'filed' => ApplicationModel::whereBetween('submitted_at', [$month, $month->copy()->endOfMonth()])
                    ->count(),
            ]),
        ];
    }
}
