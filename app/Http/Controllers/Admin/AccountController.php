<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Mailer;
use App\Http\Requests\Admin\InviteAccountRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Http\Requests\Admin\UpdateCredentialsRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Support\ListPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller implements HasMiddleware
{
    // Adding an administrator is switched off unless config('app.admin_invites') (FEATURE_ADD_ADMIN).
    // Checked before the form request, which is shared with attaching a representative's account.
    public static function middleware(): array
    {
        return [
            new Middleware(fn ($request, $next) => config('app.admin_invites') ? $next($request) : abort(404), only: ['create', 'store']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $accounts = ListPagination::paginate(User::query()
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
            ->orderBy('name'), $request);

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

        $sent = Mailer::send($admin, new AccountInvitation);
        AuditLog::record('account.admin_created', $admin, ['email' => $admin->email]);

        return redirect()->route('admin.accounts.index')->with($sent
            ? ['status' => "Invitation sent to {$admin->email}. The account becomes usable once they set a password."]
            : ['error' => "The account for {$admin->email} was created, but the invitation email could not be sent. Use Resend invitation once mail works."]);
    }

    // A representative's login, corrected by the office, optionally with a new password that the admin
    // hands over in person. A new password also signs the account out everywhere and counts as
    // verifying it, so a never-accepted invitation becomes a working login.
    public function update(UpdateCredentialsRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email', 'phone']));
        $changed = array_keys($user->getDirty());

        if ($request->validated('new_password')) {
            $user->forceFill([
                'password' => $request->validated('new_password'),
                'remember_token' => Str::random(60),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $user->save();

        if ($changed) {
            AuditLog::record('account.credentials_updated', $user, ['changed' => $changed]);
        }
        if ($request->validated('new_password')) {
            AuditLog::record('account.password_reset', $user);
        }

        return back()->with('status', $request->validated('new_password')
            ? "{$user->name}'s password was reset and they were signed out. Give them the new password in person."
            : "{$user->name}'s login details were saved.");
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

        if (! Mailer::send($user, new AccountInvitation)) {
            return back()->with('error', "The invitation email to {$user->email} could not be sent. Check the mail settings in System check.");
        }
        AuditLog::record('account.invitation_sent', $user);

        return back()->with('status', "A new invitation link was sent to {$user->email}.");
    }
}
