@php $layout = $user->isAdmin() ? 'layouts.admin' : 'layouts.cso'; @endphp

<x-dynamic-component :component="$layout" header="Account settings" subheader="Your sign-in details. Organization details are on the organization profile.">
    <div class="max-w-2xl space-y-6">
        <section class="panel p-6">
            <h2 class="font-semibold text-ink">Your details</h2>

            <form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="field-label">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                           class="field-input @error('name') field-input-error @enderror">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           aria-describedby="email-hint" class="field-input @error('email') field-input-error @enderror">
                    <p id="email-hint" class="field-hint">Changing it means confirming the new address before you can continue.</p>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror

                    @unless ($user->hasVerifiedEmail())
                        <p class="mt-2 text-sm text-warning-600">
                            This address isn't confirmed yet.
                            <button form="send-verification" class="font-semibold underline">Send the confirmation link again</button>
                        </p>
                    @endunless
                </div>

                <div>
                    <label for="phone" class="field-label">
                        Mobile number @if ($user->isAdmin())<span class="font-normal text-muted">(optional)</span>@endif
                    </label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" placeholder="0917 123 4567" autocomplete="tel"
                           class="field-input @error('phone') field-input-error @enderror">
                    @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">Save details</button>
                    @if (session('status') === 'profile-updated')
                        <p role="status" class="text-sm text-success-600">Saved.</p>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel p-6">
            <h2 class="font-semibold text-ink">Change password</h2>

            <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="field-label">Current password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                           class="field-input @error('current_password', 'updatePassword') field-input-error @enderror">
                    @error('current_password', 'updatePassword')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <x-password-field label="New password" autocomplete="new-password" checklist bag="updatePassword" />
                <x-password-field name="password_confirmation" label="Type the new password again" autocomplete="new-password" bag="updatePassword" />

                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">Change password</button>
                    @if (session('status') === 'password-updated')
                        <p role="status" class="text-sm text-success-600">Password changed.</p>
                    @endif
                </div>
            </form>
        </section>

        <p class="text-sm text-muted">
            To close this account, contact the Civil Society Desk Office. Accounts are deactivated rather than deleted so the organization's record stays intact.
        </p>
    </div>
</x-dynamic-component>
