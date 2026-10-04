<x-guest-layout>
    <x-slot:title>Reset your password</x-slot:title>
    <x-slot:heading>Reset your password</x-slot:heading>
    <x-slot:intro>Enter the email you registered with. We'll send a link to choose a new password.</x-slot:intro>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="field-input @error('email') field-input-error @enderror">
            @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary w-full">Email me a reset link</button>

        <p class="text-center text-sm text-muted">
            Remembered it? <a href="{{ route('login') }}" class="font-semibold text-navy-700 hover:underline">Sign in</a>
        </p>
    </form>
</x-guest-layout>
