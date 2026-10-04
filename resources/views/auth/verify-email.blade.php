<x-guest-layout>
    <x-slot:title>Confirm your email</x-slot:title>
    <x-slot:heading>Check your email</x-slot:heading>
    <x-slot:intro>
        We sent a confirmation link to {{ auth()->user()->email }}. Open it to finish setting up your
        account. You can apply for accreditation once your email is confirmed.
    </x-slot:intro>

    @if (session('status') === 'verification-link-sent')
        <p role="status" class="mb-6 rounded-sm border border-success-600 bg-success-100 px-4 py-3 text-sm font-medium text-success-600">
            A new confirmation link has been sent.
        </p>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full">Send the link again</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost w-full">Sign out</button>
        </form>
    </div>

    <p class="mt-8 text-sm text-muted">
        Wrong email address? Sign out and register again, or contact the Civil Society Desk Office.
    </p>
</x-guest-layout>
