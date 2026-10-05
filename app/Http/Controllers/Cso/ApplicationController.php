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

        return view('cso.applications.index', [
            'applications' => auth()->user()->organization
                ?->applications()
                ->with('accreditation')
                ->latest('id')
                ->get() ?? collect(),
            'organization' => auth()->user()->organization,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ApplicationModel::class);

        $organization = auth()->user()->organization;

        return view('cso.applications.create', [
            'organization' => $organization,
            'documentTypes' => config('document_types'),
            'hasActiveAccreditation' => $organization->accreditations()->where('status', 'active')->exists(),
        ]);
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
