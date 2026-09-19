<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('admin.organizations.index', [
            'organizations' => Organization::query()
                ->with(['accreditations' => fn ($q) => $q->where('status', 'active')])
                ->withCount(['activities as verified_activities_count' => fn ($q) => $q->where('status', 'verified')])
                ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /**
     * Directory moderation: controls whether the organization appears on the public site.
     */
    public function toggleVisibility(Organization $organization): RedirectResponse
    {
        $this->authorize('moderate', $organization);

        $organization->update(['public_visibility' => ! $organization->public_visibility]);

        return back()->with('status', $organization->public_visibility
            ? "{$organization->name} is now listed in the public directory."
            : "{$organization->name} has been hidden from the public directory.");
    }
}
