<x-guest-layout>
    <x-slot:title>Choose a new password</x-slot:title>
    <x-slot:heading>Choose a new password</x-slot:heading>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="field-input @error('email') field-input-error @enderror">
            @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <x-password-field label="New password" autocomplete="new-password" checklist />
        <x-password-field name="password_confirmation" label="Type the new password again" autocomplete="new-password" />

        <button type="submit" class="btn-primary w-full">Save new password</button>
    </form>
</x-guest-layout>
