<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Confirms a new account's email with the 6-digit code mailed to it (App\Models\User handles the
 * code itself: hashing, expiry, and the limit on wrong tries).
 */
class VerifyEmailCodeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $code = preg_replace('/\D/', '', (string) $request->input('code'));
        $request->merge(['code' => $code])->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Enter the 6-digit code from the email.', 'code.digits' => 'The code has 6 digits.'],
        );

        if ($problem = $user->confirmEmailWithCode($code)) {
            return back()->withErrors(['code' => $problem]);
        }

        event(new Verified($user));

        // Straight to the role's own dashboard: going through /dashboard would spend the message on that redirect.
        $home = route($user->isAdmin() ? 'admin.dashboard' : 'cso.dashboard', absolute: false);

        return redirect()->intended($home.'?verified=1')->with('status', 'Email confirmed. Welcome to the CSO Desk Office.');
    }
}
