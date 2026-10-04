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
            'barangays' => config('barangays'),
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

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
