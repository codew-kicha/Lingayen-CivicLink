<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
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
            ],
            'recentApplications' => ApplicationModel::with('organization')
                ->whereIn('status', ['submitted', 'under_review'])
                ->latest('submitted_at')
                ->limit(8)
                ->get(),
            'recentActivities' => Activity::with('organization')
                ->where('status', 'pending')
                ->latest('created_at')
                ->limit(8)
                ->get(),
            'organizationCount' => Organization::count(),
        ]);
    }
}
