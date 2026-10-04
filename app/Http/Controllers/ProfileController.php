<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

// Account settings. There is deliberately no self-delete: accounts are office-managed records, and
// deleting one would erase the audit trail (and could remove the last admin). The Civil Society Desk
// Office deactivates accounts instead.
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user()->fill($request->validated());
        $changed = array_keys($user->getDirty());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($changed) {
            AuditLog::record('account.profile_updated', $user, ['changed' => $changed]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
