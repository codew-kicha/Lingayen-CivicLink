<x-guest-layout>
    <x-slot:title>Set your password</x-slot:title>

    @if ($usable)
        <x-slot:heading>Set your password</x-slot:heading>
        <x-slot:intro>
            The Civil Society Desk Office created this account
            {{ $user->organization ? 'for '.$user->organization->name : 'for you' }}.
            Choose a password only you know.
        </x-slot:intro>

        <form method="POST" action="{{ request()->fullUrl() }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" type="email" value="{{ $user->email }}" readonly autocomplete="username"
                       class="field-input bg-surface text-muted">
            </div>

            <x-password-field autocomplete="new-password" checklist />
            <x-password-field name="password_confirmation" label="Type the password again" autocomplete="new-password" />

            <button type="submit" class="btn-primary w-full">Set password and sign in</button>
        </form>
    @else
        <x-slot:heading>This link has already been used</x-slot:heading>
        <x-slot:intro>
            The account's password has already been set, or the account is no longer active.
        </x-slot:intro>

        <div class="space-y-3">
            <a href="{{ route('login') }}" class="btn-primary w-full">Sign in</a>
            <a href="{{ route('password.request') }}" class="btn-ghost w-full">Forgot your password?</a>
        </div>
    @endif
</x-guest-layout>
