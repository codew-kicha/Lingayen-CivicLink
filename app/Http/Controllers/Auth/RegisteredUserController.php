<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterOrganizationRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'sectors' => config('sectors'),
        ]);
    }

    // Public self-registration is CSO-only; admin accounts are never self-registered (PRD §11).
    // The account and its organization are created together, since one exists for the other.
    public function store(RegisterOrganizationRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'),
                'role' => 'cso_rep',
            ]);

            $user->organization()->create([
                'name' => $request->validated('organization_name'),
                'sector' => $request->validated('sector'),
                'barangay' => $request->validated('barangay'),
            ]);

            AuditLog::record('account.registered', $user, actor: $user);

            return $user;
        });

        Auth::login($user);

        // Sends the confirmation code. If the mail server is down the account still exists and is
        // signed in, so the person waits on the code page and presses "Send a new code" later.
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('verification.notice')
                ->withErrors(['code' => 'Your account was created, but the code email could not be sent just now. Press "Send a new code" in a few minutes.']);
        }

        return redirect()->route('verification.notice');
    }
}
