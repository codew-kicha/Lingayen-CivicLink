<x-guest-layout>
    <x-slot:title>Confirm it's you</x-slot:title>
    <x-slot:heading>Confirm it's you</x-slot:heading>
    <x-slot:intro>
        This action changes who can access the system, so we ask for your password again.
        You won't be asked again for the next few hours.
    </x-slot:intro>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf
        <x-password-field />
        <button type="submit" class="btn-primary w-full">Confirm and continue</button>
    </form>
</x-guest-layout>
