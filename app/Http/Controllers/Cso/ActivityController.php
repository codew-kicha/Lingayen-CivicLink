<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Models\Organization;
use App\Support\ListPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $organization = auth()->user()->organization;

        return view('cso.activities.index', [
            'organization' => $organization,
            'activities' => $organization ? ListPagination::paginate($organization->activities()
                ->with(['verifiedBy', 'partnerOrganizations:id,name'])
                ->when(in_array($request->input('status'), ['pending', 'verified', 'rejected'], true), fn ($q) => $q->where('status', $request->input('status')))
                ->latest('activity_date'),
                $request) : collect(),
            'partnerOptions' => $organization ? Organization::partnerOptions($organization) : [],
            // Activities other organizations logged that credit this one as a partner.
            'taggedIn' => $organization
                ?->partneredActivities()
                ->with('organization:id,name')
                ->latest('activity_date')
                ->limit(10)
                ->get() ?? collect(),
            'taggedInTotal' => $organization?->partneredActivities()->count() ?? 0,
            'canLog' => (bool) $organization?->isAccredited(),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $request->organization()->logActivity($request->validated(), $request->user());

        return redirect()
            ->route('cso.activities.index')
            ->with('status', 'Activity logged. It will count toward your score once the Desk Office verifies it.');
    }

    // Editable (and withdrawable) only while pending: once PESO has decided, the record stands.
    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('cso.activities.edit', [
            'activity' => $activity->load('partnerOrganizations:id'),
            'partnerOptions' => Organization::partnerOptions($activity->organization),
        ]);
    }

    public function update(StoreActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity->revise($request->validated());

        return redirect()->route('cso.activities.index')->with('status', "“{$activity->title}” was updated.");
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('withdraw', $activity);

        $activity->withdraw();

        return redirect()->route('cso.activities.index')->with('status', "“{$activity->title}” was withdrawn.");
    }
}
