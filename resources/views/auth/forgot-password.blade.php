<x-guest-layout>
    <x-slot:title>Reset your password</x-slot:title>
    <x-slot:heading>Reset your password</x-slot:heading>
    <x-slot:intro>Enter the email you registered with. We'll send a link to choose a new password.</x-slot:intro>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <x-email-field autocomplete="username" autofocus />

        <button type="submit" class="btn-primary w-full">Email me a reset link</button>

        <p class="text-center text-sm text-muted">
            Remembered it? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Sign in</a>
        </p>
    </form>
</x-guest-layout>
