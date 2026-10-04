<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteAccountRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $accounts = User::query()
            ->with('organization:id,user_id,name')
            ->when($request->input('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->input('status'), fn ($q, $status) => match ($status) {
                'active' => $q->where('is_active', true)->whereNotNull('email_verified_at'),
                'pending' => $q->where('is_active', true)->whereNull('email_verified_at'),
                'deactivated' => $q->where('is_active', false),
                default => $q,
            })
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhereHas('organization', fn ($q) => $q->where('name', 'like', "%{$term}%"))))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.accounts.index', ['accounts' => $accounts]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.accounts.create');
    }

    // Creates another PESO administrator. Behind password.confirm (route) because it grants full access.
    public function store(InviteAccountRequest $request): RedirectResponse
    {
        $admin = User::create($request->validated() + [
            'role' => 'admin',
            'password' => Str::random(64),
        ]);

        $admin->notify(new AccountInvitation);
        AuditLog::record('account.admin_created', $admin, ['email' => $admin->email]);

        return redirect()->route('admin.accounts.index')
            ->with('status', "Invitation sent to {$admin->email}. The account becomes usable once they set a password.");
    }

    public function deactivate(ReasonRequest $request, User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $user->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $request->validated('reason'),
        ])->save();

        AuditLog::record('account.deactivated', $user, ['reason' => $request->validated('reason')]);

        return back()->with('status', "{$user->name} has been deactivated and signed out.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorize('reactivate', $user);

        $user->forceFill(['is_active' => true, 'deactivated_at' => null, 'deactivation_reason' => null])->save();
        AuditLog::record('account.reactivated', $user);

        return back()->with('status', "{$user->name} can sign in again.");
    }

    public function resendInvitation(User $user): RedirectResponse
    {
        $this->authorize('invite', $user);

        $user->notify(new AccountInvitation);
        AuditLog::record('account.invitation_sent', $user);

        return back()->with('status', "A new invitation link was sent to {$user->email}.");
    }
}
