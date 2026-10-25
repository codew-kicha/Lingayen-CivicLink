{{-- Step after registering: type the 6-digit code emailed to the account (VerifyEmailCodeController). --}}
<x-guest-layout>
    <x-slot:title>Confirm your email</x-slot:title>
    <x-slot:heading>Check your email</x-slot:heading>
    <x-slot:intro>
        We sent a 6-digit code to <strong class="font-semibold text-ink">{{ auth()->user()->email }}</strong>.
        Enter it below to confirm your email. It expires in {{ \App\Models\User::EMAIL_CODE_MINUTES }} minutes.
    </x-slot:intro>

    @if (session('status') === 'verification-link-sent')
        <p role="status" class="mb-6 rounded-sm border border-success bg-success-subtle px-4 py-3 text-sm font-medium text-success">
            A new code is on its way. Use the newest one; earlier codes no longer work.
        </p>
    @endif

    <form method="POST" action="{{ route('verification.code') }}">
        @csrf
        <label for="code" class="field-label">Confirmation code</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9 ]{6,7}" maxlength="7" required autofocus placeholder="000000"
               @error('code') aria-invalid="true" aria-describedby="code-error" @enderror
               class="field-input text-center font-mono text-2xl tracking-[0.5em] @error('code') field-input-error @enderror">
        @error('code')<p id="code-error" class="field-error">{{ $message }}</p>@enderror

        <button type="submit" class="btn-primary mt-6 w-full">Confirm email</button>
    </form>

    <div class="mt-6 border-t border-line pt-6 text-sm text-muted">
        <p>No email? Check your spam or promotions folder, or send a new code.</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-secondary">Send a new code</button>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                @csrf
                <button type="submit" class="btn-ghost">Sign out</button>
            </form>
        </div>
        <p class="mt-4">Wrong email address? Sign out and register again, or contact the Civil Society Desk Office.</p>
    </div>
</x-guest-layout>
