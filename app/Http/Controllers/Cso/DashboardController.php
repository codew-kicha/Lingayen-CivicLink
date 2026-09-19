<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $organization = auth()->user()->organization;

        return view('cso.dashboard', [
            'organization' => $organization,
            'accreditation' => $organization?->accreditations()->where('status', 'active')->first(),
            'latestApplication' => $organization?->applications()->latest('id')->first(),
            'score' => $organization?->performanceScores()->latest('computed_at')->first(),
            'pendingActivities' => $organization?->activities()->where('status', 'pending')->count() ?? 0,
            'verifiedActivities' => $organization?->activities()->where('status', 'verified')->count() ?? 0,
            'expiringDocuments' => $organization
                ?->documents()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays(60))
                ->orderBy('expires_at')
                ->get() ?? collect(),
            'notifications' => auth()->user()->unreadNotifications()->limit(5)->get(),
        ]);
    }
}
