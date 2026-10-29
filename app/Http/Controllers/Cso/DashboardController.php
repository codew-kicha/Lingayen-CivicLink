<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $organization = auth()->user()->organization;
        // The latest updates, read or not; the unread ones are marked "New" this once, then read.
        $notifications = auth()->user()->notifications()->latest()->limit(5)->get();
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $accreditation = $organization?->accreditations()->where('status', 'active')->first();

        return view('cso.dashboard', [
            'organization' => $organization,
            'accreditation' => $accreditation,
            // Lapsed and not yet renewed: the dashboard says so and points to the renewal.
            'lapsed' => $accreditation ? null : $organization?->accreditations()->where('status', 'expired')->latest('expires_at')->first(),
            'latestApplication' => $organization?->applications()->latest('id')->first(),
            'score' => $organization?->performanceScores()->latest('computed_at')->first(),
            'pendingActivities' => $organization?->activities()->where('status', 'pending')->count() ?? 0,
            'verifiedActivities' => $organization?->activities()->where('status', 'verified')->count() ?? 0,
            'recentActivities' => $organization?->activities()->latest('activity_date')->latest('id')->limit(5)->get() ?? collect(),
            'expiringDocuments' => $organization
                ?->documents()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays(60))
                ->orderBy('expires_at')
                ->get() ?? collect(),
            'notifications' => $notifications,
        ]);
    }
}
