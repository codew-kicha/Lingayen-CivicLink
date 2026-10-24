<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrganizationList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmedOrganizationRequest;
use App\Http\Requests\Admin\InviteAccountRequest;
use App\Http\Requests\Admin\ManageOrganizationRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Services\OrganizationRecords;
use App\Services\ReportData;
use App\Support\ListPagination;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class OrganizationModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('admin.organizations.index', [
            'organizations' => ListPagination::paginate(Organization::query()
                ->with(['accreditations' => fn ($q) => $q->where('status', 'active'), 'user:id,name,email,is_active,email_verified_at'])
                ->withCount(['activities as verified_activities_count' => fn ($q) => $q->where('status', 'verified')])
                ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->when($request->input('account') === 'none', fn ($q) => $q->whereNull('user_id'))
                ->when($request->input('status') === 'inactive', fn ($q) => $q->inactive())
                // Same definition and period as the analytics at-risk list and its Excel sheet.
                ->when($request->input('status') === 'at-risk', fn ($q) => $q->whereKey(ReportData::fromRequest($request)->atRisk()->pluck('id')))
                ->orderBy('name'),
                $request),
            'inactiveIds' => Organization::inactive()->pluck('id'),
        ]);
    }

    // The CSO list as an Excel file: all registered organizations, or only the accredited ones.
    // It includes representatives' contact details, so it is admin-only and audited.
    public function export(Request $request): Response
    {
        $this->authorize('manage', Organization::class);

        $accreditedOnly = $request->input('scope') === 'accredited';
        AuditLog::record('organization.list_exported', null, ['scope' => $accreditedOnly ? 'accredited' : 'all']);

        return Excel::download(
            OrganizationList::sheet($accreditedOnly),
            ($accreditedOnly ? 'accredited' : 'registered').'-csos-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    // Registering happens in a dialog; this address opens it on the Organizations list.
    public function create(): RedirectResponse
    {
        $this->authorize('manage', Organization::class);

        return redirect()->route('admin.organizations.index', ['register' => 1]);
    }

    // Registers an organization on a CSO's behalf (walk-ins, imported records). With "Create a login"
    // ticked, the representative's account is created and invited by email to set a password.
    public function store(ManageOrganizationRequest $request): RedirectResponse
    {
        $organization = DB::transaction(function () use ($request) {
            $user = null;

            if ($request->validated('rep_email')) {
                $user = User::create([
                    'name' => $request->validated('rep_name'),
                    'email' => $request->validated('rep_email'),
                    'phone' => $request->validated('rep_phone'),
                    'role' => 'cso_rep',
                    'password' => Str::random(64),
                ]);
            }

            $organization = Organization::create($request->safe()->only(['name', 'sector', 'barangay', 'advocacy']) + [
                'user_id' => $user?->id,
            ]);

            AuditLog::record('organization.created', $organization, ['representative' => $user?->email]);

            return $organization->setRelation('user', $user);
        });

        $organization->user?->notify(new AccountInvitation);

        return redirect()->route('admin.organizations.edit', $organization)->with('status', $organization->user
            ? "{$organization->name} registered. An invitation was sent to {$organization->user->email}."
            : "{$organization->name} registered without a login. Attach a representative when one is known.");
    }

    public function edit(Organization $organization): View
    {
        $this->authorize('manage', Organization::class);

        return view('admin.organizations.form', [
            'organization' => $organization->load(['user', 'accreditations' => fn ($q) => $q->latest('issued_at')]),
            'sectors' => config('sectors'),
            'barangays' => config('barangays'),
            'timeline' => ReportData::timeline($organization),
            // For the Delete and Merge dialogs.
            'footprint' => OrganizationRecords::footprint($organization),
            'mergeOptions' => Organization::whereKeyNot($organization->id)->orderBy('name')->get(['id', 'name', 'barangay'])
                ->map(fn ($other) => ['id' => $other->id, 'name' => "{$other->name} · Brgy. {$other->barangay}"])->all(),
            'history' => AuditLog::with('actor:id,name')
                ->where('subject_type', 'Organization')
                ->where('subject_id', $organization->id)
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    // Member names and contacts are never public (PRD §8), so this printable is admin-only and audited.
    public function members(Organization $organization): Response
    {
        $this->authorize('manage', Organization::class);

        AuditLog::record('organization.members_printed', $organization);

        return Pdf::loadView('pdf.members', [
            'organization' => $organization->load(['members' => fn ($q) => $q->orderBy('id')]),
            'printedAt' => now(),
            'printedBy' => request()->user()->name,
        ])->download('officers-members-'.Str::slug($organization->name).'.pdf');
    }

    public function update(ManageOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->fill($request->safe()->only(['name', 'sector', 'barangay', 'advocacy']));
        $changes = $organization->getDirty();
        $organization->save();

        if ($changes) {
            AuditLog::record('organization.updated', $organization, ['changed' => array_keys($changes)]);
        }

        return back()->with('status', 'Organization details saved.');
    }

    public function attachAccount(InviteAccountRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('manage', Organization::class);
        abort_if($organization->user_id !== null, 409, 'This organization already has a representative account.');

        $user = DB::transaction(function () use ($request, $organization) {
            $user = User::create($request->validated() + ['role' => 'cso_rep', 'password' => Str::random(64)]);
            $organization->update(['user_id' => $user->id]);
            AuditLog::record('organization.account_attached', $organization, ['email' => $user->email]);

            return $user;
        });

        $user->notify(new AccountInvitation);

        return back()->with('status', "Invitation sent to {$user->email}.");
    }

    // For test entries and mistakes. Everything belonging to the organization goes with it,
    // including its representative's login; the audit log keeps its name and what was removed.
    public function destroy(ConfirmedOrganizationRequest $request, Organization $organization, OrganizationRecords $records): RedirectResponse
    {
        $records->delete($organization);

        return redirect()->route('admin.organizations.index')
            ->with('status', "{$organization->name} and everything filed under it were deleted.");
    }

    // For duplicates (common after an import): the history moves onto the record that stays.
    public function merge(ConfirmedOrganizationRequest $request, Organization $organization, OrganizationRecords $records): RedirectResponse
    {
        $into = Organization::findOrFail($request->validated('into'));
        $records->merge($organization, $into);

        return redirect()->route('admin.organizations.edit', $into)
            ->with('status', "{$organization->name} was merged into {$into->name}. Its applications, activities and documents are now here.");
    }

    // PESO had no way to withdraw an accreditation (PRD §2); this records it with a reason.
    public function revoke(ReasonRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('manage', Organization::class);

        $accreditation = $organization->accreditations()->where('status', 'active')->firstOrFail();
        $accreditation->update(['status' => 'revoked', 'active_org_marker' => null]);

        AuditLog::record('accreditation.revoked', $organization, [
            'verification_code' => $accreditation->verification_code,
            'reason' => $request->validated('reason'),
        ]);

        return back()->with('status', "Accreditation {$accreditation->verification_code} has been revoked.");
    }

    public function toggleVisibility(Organization $organization): RedirectResponse
    {
        $this->authorize('moderate', $organization);

        $organization->update(['public_visibility' => ! $organization->public_visibility]);
        AuditLog::record($organization->public_visibility ? 'organization.listed' : 'organization.hidden', $organization);

        return back()->with('status', $organization->public_visibility
            ? "{$organization->name} is now listed in the public directory."
            : "{$organization->name} has been hidden from the public directory.");
    }
}
