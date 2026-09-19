<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\ApplicationModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        $organization = $request->user()->organization;

        $application = DB::transaction(function () use ($request, $organization) {
            $application = $organization->applications()->create([
                'type' => $request->validated('type'),
                'status' => 'submitted',
                'submission_channel' => 'online',
                'submitted_by' => $request->user()->id,
                'submitted_at' => now(),
            ]);

            foreach ($request->file('documents', []) as $type => $file) {
                if (! $file) {
                    continue;
                }

                $organization->documents()->create([
                    'application_id' => $application->id,
                    'document_type' => $type,
                    'file_path' => $file->store("documents/{$organization->id}"),
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'expires_at' => $request->input("expires_at.{$type}") ?: null,
                ]);
            }

            return $application;
        });

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
