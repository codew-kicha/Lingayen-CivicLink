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
    // The form is a dialog on the organization page now; this address opens it there.
    public function createApplication(Organization $organization): RedirectResponse
    {
        $this->authorize('manage', Organization::class);

        return redirect()->route('admin.organizations.edit', [$organization, 'file' => 1]);
    }

    public function storeApplication(StoreApplicationRequest $request, Organization $organization): RedirectResponse
    {
        $application = $organization->submitApplication(
            $request->validated('type'),
            $request->file('documents', []),
            $request->input('expires_at', []),
            $request->user(),
            'assisted',
            $request->input('ocr_text', []),
        );

        AuditLog::record('application.assisted', $organization, ['application_id' => $application->id]);

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Application encoded for {$organization->name}. It is now in the review queue.");
    }

    public function createActivity(Organization $organization): View|RedirectResponse
    {
        $this->authorize('manage', Organization::class);

        if (! $organization->isAccredited()) {
            return redirect()->route('admin.organizations.edit', $organization)
                ->with('error', 'Only accredited organizations can log activities. Approve an application for it first.');
        }

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
