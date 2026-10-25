<x-layouts.app header="Account settings" subheader="Your sign-in details. Organization details are on the organization profile.">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,40rem)_minmax(0,1fr)]">
        <div class="space-y-6">
            <section class="card p-6" aria-labelledby="details-title">
                <h2 id="details-title" class="card-title">Your details</h2>

                <form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>

                <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="name" class="field-label">Full name</label>
                            <input id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                                   class="field-input @error('name') field-input-error @enderror"
                                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                            @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="position" class="field-label">Position <span class="font-normal text-muted">(optional)</span></label>
                            <input id="position" name="position" value="{{ old('position', $user->position) }}" maxlength="120"
                                   autocomplete="organization-title" placeholder="{{ $user->isAdmin() ? 'e.g. CSO Desk Officer' : 'e.g. President' }}"
                                   aria-describedby="position-hint" class="field-input @error('position') field-input-error @enderror">
                            <p id="position-hint" class="field-hint">Shown under your name in the sidebar.</p>
                            @error('position')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="field-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                               aria-describedby="email-hint" class="field-input @error('email') field-input-error @enderror">
                        <p id="email-hint" class="field-hint">Changing it means confirming the new address before you can continue.</p>
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror

                        @unless ($user->hasVerifiedEmail())
                            <p class="mt-2 text-sm text-warning">
                                This address isn't confirmed yet.
                                <button form="send-verification" class="font-semibold underline">Send the confirmation link again</button>
                            </p>
                        @endunless
                    </div>

                    {{-- Format hint plus a browser check; the server still validates and normalizes to +639XXXXXXXXX. --}}
                    <div x-data="{ touched: false }">
                        <label for="phone" class="field-label">
                            Mobile number @if ($user->isAdmin())<span class="font-normal text-muted">(optional)</span>@endif
                        </label>
                        <input id="phone" x-ref="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', $user->phone) }}"
                               placeholder="+63 917 123 4567" autocomplete="tel" @required(! $user->isAdmin())
                               pattern="^(\+?63|0)[\s\-]?9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}$"
                               @blur="touched = true" :class="{ 'field-input-error': touched && ! $el.validity.valid }"
                               aria-describedby="phone-hint phone-check" class="field-input @error('phone') field-input-error @enderror">
                        <p id="phone-hint" class="field-hint">Format: +63 9XX XXX XXXX. Starting with 09 also works.</p>
                        <p id="phone-check" class="field-error" x-cloak x-show="touched && ! $refs.phone.validity.valid" aria-live="polite">
                            Enter a Philippine mobile number, like +63 917 123 4567.
                        </p>
                        @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn-primary">Save details</button>
                        @if (session('status') === 'profile-updated')
                            <p role="status" class="text-sm text-success">Saved.</p>
                        @endif
                    </div>
                </form>
            </section>

            <section class="card p-6" aria-labelledby="password-title">
                <h2 id="password-title" class="card-title">Change password</h2>

                <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-6">
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

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn-primary">Change password</button>
                        @if (session('status') === 'password-updated')
                            <p role="status" class="text-sm text-success">Password changed.</p>
                        @endif
                    </div>
                </form>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-6" aria-labelledby="account-title">
                <h2 id="account-title" class="card-title">Account</h2>
                <dl class="mt-4 divide-y divide-line text-sm">
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-muted">Role</dt>
                        <dd class="text-ink">{{ $user->isAdmin() ? 'PESO Admin' : 'CSO Representative' }}</dd>
                    </div>
                    @if ($user->organization)
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="shrink-0 text-muted">Organization</dt>
                            <dd class="text-right text-ink">{{ $user->organization->name }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-muted">Email</dt>
                        <dd>
                            @if ($user->hasVerifiedEmail())
                                <span class="badge-success">Confirmed</span>
                            @else
                                <span class="badge-warning">Not confirmed</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-muted">Last sign-in</dt>
                        <dd class="whitespace-nowrap tabular-nums text-ink">{{ $user->last_login_at?->format('d M Y, g:i A') ?? 'Not recorded' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card p-6" aria-labelledby="security-title">
                <h2 id="security-title" class="card-title">Security</h2>
                <ul class="mt-4 space-y-3 text-sm text-muted">
                    <li class="flex gap-3"><x-phosphor-lock-key class="h-5 w-5 shrink-0 text-primary" aria-hidden="true" />Passwords need 12 or more characters, with upper and lower case, a number and a symbol.</li>
                    <li class="flex gap-3"><x-phosphor-clock-counter-clockwise class="h-5 w-5 shrink-0 text-primary" aria-hidden="true" />Five failed sign-ins lock the account for a short time.</li>
                    <li class="flex gap-3"><x-phosphor-prohibit class="h-5 w-5 shrink-0 text-primary" aria-hidden="true" />To close this account, contact the Civil Society Desk Office. Accounts are deactivated, not deleted, so the organization's record stays intact.</li>
                </ul>
            </section>
        </aside>
    </div>
</x-layouts.app>
