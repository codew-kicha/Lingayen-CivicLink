<x-guest-layout>
    <x-slot:title>Sign in</x-slot:title>
    <x-slot:heading>Sign in</x-slot:heading>
    <x-slot:intro>For CSO representatives and Civil Society Desk Office staff.</x-slot:intro>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="field-input @error('email') field-input-error @enderror">
            @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <x-password-field />

        <div class="flex flex-wrap items-center justify-between gap-x-4">
            <label for="remember_me" class="flex min-h-11 items-center gap-2 text-sm text-ink">
                <input id="remember_me" name="remember" type="checkbox" class="rounded-sm border-line-strong text-brand-600">
                Keep me signed in
            </label>
            <a href="{{ route('password.request') }}" class="flex min-h-11 items-center text-sm font-semibold text-brand-700 hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>

    <x-slot:footer>
        New organization?
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Register your organization</a>
    </x-slot:footer>
</x-guest-layout>
