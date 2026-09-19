<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
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
                ->with('verifiedBy')
                ->latest('activity_date')
                ->paginate(15) ?? collect(),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $request->user()->organization->activities()->create([
            ...$request->validated(),
            'logged_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('cso.activities.index')
            ->with('status', 'Activity logged. It will count toward your score once PESO verifies it.');
    }
}
