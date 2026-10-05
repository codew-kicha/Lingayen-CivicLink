<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * PESO staff encode paper submissions for organizations that cannot file online (PRD §6.1.12).
 * Same requests and model methods as the CSO forms; only the submitter and channel differ.
 */
class AssistedEncodingController extends Controller
{
    public function createApplication(Organization $organization): View
    {
        $this->authorize('manage', Organization::class);

        return view('admin.assisted.application', [
            'organization' => $organization,
            'documentTypes' => config('document_types'),
            'hasActiveAccreditation' => $organization->accreditations()->where('status', 'active')->exists(),
        ]);
    }

    public function storeApplication(StoreApplicationRequest $request, Organization $organization): RedirectResponse
    {
        $application = $organization->submitApplication(
            $request->validated('type'),
            $request->file('documents', []),
            $request->input('expires_at', []),
            $request->user(),
            'assisted',
        );

        AuditLog::record('application.assisted', $organization, ['application_id' => $application->id]);

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Application encoded for {$organization->name}. It is now in the review queue.");
    }

    public function createActivity(Organization $organization): View
    {
        $this->authorize('manage', Organization::class);

        return view('admin.assisted.activity', [
            'organization' => $organization,
            'partnerOptions' => Organization::partnerOptions($organization),
        ]);
    }

    public function storeActivity(StoreActivityRequest $request, Organization $organization): RedirectResponse
    {
        $activity = $organization->logActivity($request->validated(), $request->user());

        AuditLog::record('activity.assisted', $organization, ['activity_id' => $activity->id]);

        return redirect()->route('admin.activities.index')
            ->with('status', "Activity encoded for {$organization->name}. Verify it below once the evidence is checked.");
    }
}
