<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteAccountRequest;
use App\Http\Requests\Admin\ManageOrganizationRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrganizationModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('admin.organizations.index', [
            'organizations' => Organization::query()
                ->with(['accreditations' => fn ($q) => $q->where('status', 'active'), 'user:id,name,email,is_active,email_verified_at'])
                ->withCount(['activities as verified_activities_count' => fn ($q) => $q->where('status', 'verified')])
                ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->when($request->input('account') === 'none', fn ($q) => $q->whereNull('user_id'))
                ->when($request->input('status') === 'inactive', fn ($q) => $q->inactive())
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'inactiveIds' => Organization::inactive()->pluck('id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage', Organization::class);

        return view('admin.organizations.form', [
            'organization' => new Organization,
            'sectors' => config('sectors'),
            'barangays' => config('barangays'),
        ]);
    }

    // Registers an organization on a CSO's behalf (walk-ins, imported records). If a representative
    // is given, their account is created and invited by email; the admin never sets a password.
    public function store(ManageOrganizationRequest $request): RedirectResponse
    {
        $organization = DB::transaction(function () use ($request) {
            $user = null;

            if ($request->filled('rep_email')) {
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
            'history' => AuditLog::with('actor:id,name')
                ->where('subject_type', 'Organization')
                ->where('subject_id', $organization->id)
                ->latest('created_at')
                ->limit(15)
                ->get(),
        ]);
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
