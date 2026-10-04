<x-guest-layout>
    <x-slot:title>Sign in</x-slot:title>
    <x-slot:heading>Sign in</x-slot:heading>
    <x-slot:intro>For CSO representatives and Civil Society Desk Office staff.</x-slot:intro>

    <x-slot:aside>
        <p class="max-w-[30ch] text-2xl font-semibold leading-snug [text-wrap:balance]">
            Track your application, log your organization's activities, and see your record as residents see it.
        </p>
        <ul class="mt-8 space-y-3 text-navy-200">
            <li class="flex gap-3"><x-phosphor-seal-check class="h-5 w-5 shrink-0 text-rose-400" aria-hidden="true" /> Application status through each Sangguniang Bayan reading</li>
            <li class="flex gap-3"><x-phosphor-seal-check class="h-5 w-5 shrink-0 text-rose-400" aria-hidden="true" /> Activities verified by the Civil Society Desk Office</li>
            <li class="flex gap-3"><x-phosphor-seal-check class="h-5 w-5 shrink-0 text-rose-400" aria-hidden="true" /> Your organization's public profile and performance score</li>
        </ul>
    </x-slot:aside>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="field-input @error('email') field-input-error @enderror">
            @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <x-password-field />

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="flex min-h-11 items-center gap-2 text-sm text-ink">
                <input id="remember_me" name="remember" type="checkbox" class="rounded-sm border-line-strong text-navy-600">
                Keep me signed in on this device
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-navy-700 hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>

    <div class="mt-10 border-t border-line pt-6">
        <p class="font-semibold text-ink">Your organization isn't registered yet?</p>
        <p class="mt-1 text-sm text-muted">Create an account for your CSO, then file your accreditation application online.</p>
        <a href="{{ route('register') }}" class="btn-secondary mt-4">Register your organization</a>
    </div>
</x-guest-layout>
