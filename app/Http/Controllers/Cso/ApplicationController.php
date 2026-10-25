<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
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
            // For the Apply dialog.
            'documentTypes' => config('document_types'),
            'hasActiveAccreditation' => (bool) $organization?->accreditations()->where('status', 'active')->exists(),
        ]);
    }

    // The form is a dialog on the Applications page now; this address opens it there.
    public function create(): RedirectResponse
    {
        $this->authorize('create', ApplicationModel::class);

        return redirect()->route('cso.applications.index', ['apply' => 1]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $application = $request->user()->organization->submitApplication(
            $request->validated('type'),
            $request->file('documents', []),
            $request->input('expires_at', []),
            $request->user(),
            'online',
            $request->input('ocr_text', []),
        );

        return redirect()
            ->route('cso.applications.show', $application)
            ->with('status', 'Application submitted. PESO will review it and you will be notified of any change.');
    }

    public function show(ApplicationModel $application): View
    {
        $this->authorize('view', $application);

        return view('cso.applications.show', [
            'application' => $application->load(['documents', 'accreditation', 'reviewedBy']),
        ]);
    }
}
