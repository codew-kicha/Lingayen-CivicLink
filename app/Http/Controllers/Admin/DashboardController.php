<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Organization;
use App\Models\PerformanceScore;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'counts' => [
                'awaiting_review' => ApplicationModel::whereIn('status', ['submitted', 'under_review'])->count(),
                'pending_activities' => Activity::where('status', 'pending')->count(),
                'active_accreditations' => Accreditation::where('status', 'active')->count(),
                'expiring_soon' => Accreditation::where('status', 'active')
                    ->whereBetween('expires_at', [now(), now()->addDays(90)])
                    ->count(),
                // Accredited, but no verified activity in Organization::INACTIVE_AFTER_MONTHS (PRD §3).
                'inactive' => Organization::inactive()->count(),
            ],
            'recentApplications' => ApplicationModel::with('organization')
                ->whereIn('status', ['submitted', 'under_review'])
                ->latest('submitted_at')
                ->limit(5)
                ->get(),
            'recentActivities' => Activity::with('organization')
                ->where('status', 'pending')
                ->latest('created_at')
                ->limit(5)
                ->get(),
            'organizationCount' => Organization::count(),
            // Each accredited organization's latest score, highest first (ties: name).
            'topPerformers' => PerformanceScore::query()
                ->whereIn('performance_scores.id', PerformanceScore::selectRaw('max(id)')->groupBy('organization_id'))
                ->whereHas('organization.accreditations', fn ($q) => $q->where('status', 'active'))
                ->with('organization:id,name,sector,barangay')
                ->join('organizations', 'organizations.id', '=', 'performance_scores.organization_id')
                ->orderByDesc('total_score')
                ->orderBy('organizations.name')
                ->limit(10)
                ->get('performance_scores.*'),
            'details' => $this->details(),
        ]);
    }

    /**
     * What each count opens to on the dashboard (pre-final defense panel: "more info when clicked"):
     * a breakdown, the oldest item, and the few records behind the number. Small aggregate queries.
     */
    private function details(): array
    {
        $waiting = fn () => ApplicationModel::whereIn('status', ['submitted', 'under_review']);
        $pending = fn () => Activity::where('status', 'pending');
        $days = fn ($date) => $date ? (int) Carbon::parse($date)->diffInDays(now()) : null;

        $expiring = Accreditation::with('organization:id,name')
            ->where('status', 'active')->whereBetween('expires_at', [now(), now()->addDays(90)])
            ->orderBy('expires_at')->limit(6)->get();
        $reminded = AuditLog::where('action', 'accreditation.reminder_sent')->where('subject_type', 'Organization')
            ->whereIn('subject_id', $expiring->pluck('organization_id'))
            ->selectRaw('subject_id, max(created_at) as last')->groupBy('subject_id')->pluck('last', 'subject_id');

        $year = now()->startOfYear();
        $issuedThisYear = Accreditation::where('issued_at', '>=', $year->toDateString())
            ->join('applications', 'applications.id', '=', 'accreditations.application_id')
            ->selectRaw('applications.type, count(*) as total')->groupBy('applications.type')->pluck('total', 'applications.type');

        return [
            'review' => [
                'stages' => collect(ApplicationModel::SB_STAGES)
                    ->mapWithKeys(fn ($label, $key) => [$label => $waiting()->where('sb_stage', $key)->count()]),
                'oldest' => $days($waiting()->min('submitted_at')),
                'renewals' => $waiting()->where('type', 'renewal')->count(),
                'assisted' => $waiting()->where('submission_channel', 'assisted')->count(),
                'flagged' => Document::whereIn('application_id', $waiting()->select('id'))->whereIn('ocr_status', ['mismatch', 'unreadable'])->count(),
            ],
            'activities' => [
                'sectors' => $pending()->join('organizations', 'organizations.id', '=', 'activities.organization_id')
                    ->selectRaw('organizations.sector, count(*) as total')->groupBy('organizations.sector')
                    ->orderByDesc('total')->limit(6)->pluck('total', 'organizations.sector'),
                'oldest' => $days($pending()->min('activities.created_at')),
                'withSheet' => $pending()->whereNotNull('evidence_path')->count(),
                'withoutSheet' => $pending()->whereNull('evidence_path')->count(),
            ],
            'expiring' => $expiring->map(fn (Accreditation $a) => [
                'organization' => $a->organization,
                'expires' => $a->expires_at,
                'days' => (int) now()->startOfDay()->diffInDays($a->expires_at, false),
                'reminded' => isset($reminded[$a->organization_id]) ? Carbon::parse($reminded[$a->organization_id]) : null,
            ]),
            'inactive' => Organization::inactive()
                ->withMax(['activities as last_verified' => fn ($q) => $q->where('status', 'verified')], 'activity_date')
                ->orderBy('last_verified')->limit(6)->get(['id', 'name', 'barangay']),
            'accredited' => [
                'sectors' => Accreditation::where('accreditations.status', 'active')
                    ->join('organizations', 'organizations.id', '=', 'accreditations.organization_id')
                    ->selectRaw('organizations.sector, count(*) as total')->groupBy('organizations.sector')
                    ->orderByDesc('total')->pluck('total', 'organizations.sector'),
                'new' => (int) ($issuedThisYear['new'] ?? 0),
                'renewed' => (int) ($issuedThisYear['renewal'] ?? 0),
                'months' => collect(range(11, 0))->map(function (int $back) {
                    $month = now()->startOfMonth()->subMonths($back);

                    return [
                        'label' => $month->format('M'),
                        'total' => Accreditation::whereBetween('issued_at', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->count(),
                    ];
                })->all(),
            ],
        ];
    }
}
