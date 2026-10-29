<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\PerformanceScore;
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
        ]);
    }
}
