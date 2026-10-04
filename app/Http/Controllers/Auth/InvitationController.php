<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

// Accepting an admin-sent invitation. The link is signed and expires (route middleware); it also
// stops working as soon as the account is verified, so a used or forwarded link can't be replayed.
class InvitationController extends Controller
{
    public function show(User $user): View
    {
        return view('auth.invitation', ['user' => $user, 'usable' => $this->usable($user)]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->usable($user), 410);

        $request->validate(['password' => ['required', 'confirmed', Password::defaults()]]);

        $user->forceFill([
            'password' => $request->input('password'),
            'email_verified_at' => now(),
        ])->save();

        AuditLog::record('account.invitation_accepted', $user, actor: $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function usable(User $user): bool
    {
        return $user->is_active && $user->email_verified_at === null;
    }
}
