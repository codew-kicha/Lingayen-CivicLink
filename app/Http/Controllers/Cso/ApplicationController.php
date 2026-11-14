<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ApplicationModel::class);

        $organization = auth()->user()->organization;

        return view('cso.applications.index', [
            'applications' => $organization
                ?->applications()
                ->with('accreditation')
                ->latest('id')
                ->get() ?? collect(),
            'organization' => $organization,
        ]);
    }

    // Applying is a guided set of steps now; this older address leads there.
    public function create(): RedirectResponse
    {
        $this->authorize('create', ApplicationModel::class);

        return redirect()->route('cso.application');
    }

    public function show(ApplicationModel $application): View
    {
        $this->authorize('view', $application);

        return view('cso.applications.show', [
            'application' => $application->load(['documents', 'accreditation', 'reviewedBy']),
        ]);
    }
}
