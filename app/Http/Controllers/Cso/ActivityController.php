<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Activity::class);

        $organization = auth()->user()->organization;

        return view('cso.activities.index', [
            'organization' => $organization,
            'activities' => $organization
                ?->activities()
                ->with(['verifiedBy', 'partnerOrganizations:id,name'])
                ->latest('activity_date')
                ->paginate(15) ?? collect(),
            'partnerOptions' => $organization ? Organization::partnerOptions($organization) : [],
            // Activities other organizations logged that credit this one as a partner.
            'taggedIn' => $organization
                ?->partneredActivities()
                ->with('organization:id,name')
                ->latest('activity_date')
                ->limit(10)
                ->get() ?? collect(),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $request->organization()->logActivity($request->validated(), $request->user());

        return redirect()
            ->route('cso.activities.index')
            ->with('status', 'Activity logged. It will count toward your score once PESO verifies it.');
    }
}
