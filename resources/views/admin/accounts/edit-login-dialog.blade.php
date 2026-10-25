{{--
    "Edit login" dialog (560px) for a CSO representative's account: name, email, mobile, and an
    optional new password the admin hands over in person. Rows open it with
    $dispatch('edit-login', { id, name, email, phone, organization, url }).
--}}
@php
    $reopen = $errors->any() && old('_dialog') === 'edit-login';
    $initial = $reopen ? [
        'id' => (int) old('_account'),
        'name' => old('name'),
        'email' => old('email'),
        'phone' => old('phone'),
        'organization' => old('_organization'),
        'url' => route('admin.accounts.update', (int) old('_account')),
    ] : null;
    $field = fn (string $name) => $reopen && $errors->has($name);
@endphp

<div x-data="loginEditor(@js($initial), @js($reopen && old('reset_password')))"
     @edit-login.window="edit($event.detail)">
    <x-dialog name="edit-login" title="Edit login" width="sm" :open="$reopen">
        <form method="POST" :action="account.url" class="flex min-h-0 flex-1 flex-col" autocomplete="off">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_dialog" value="edit-login">
            <input type="hidden" name="_account" :value="account.id">
            <input type="hidden" name="_organization" :value="account.organization">

            <div class="dialog-body space-y-6">
                <p class="text-sm text-muted">
                    <span x-show="account.organization">Representative of <span class="font-semibold text-ink" x-text="account.organization"></span>. </span>
                    <span class="text-danger" aria-hidden="true">*</span> Required
                </p>

                <div>
                    <label for="login-name" class="field-label field-required">Full name</label>
                    <input id="login-name" name="name" x-model="account.name" autofocus required maxlength="255"
                           @class(['field-input', 'field-input-error' => $field('name')])
                           @if ($field('name')) aria-invalid="true" aria-describedby="login-name-error" @endif>
                    @if ($field('name'))<p id="login-name-error" class="field-error">{{ $errors->first('name') }}</p>@endif
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="login-email" class="field-label field-required">Email</label>
                        {{-- autocomplete off: next to a password field, browsers treat this as a sign-in form
                             and fill in the representative's saved password. --}}
                        <input id="login-email" name="email" type="email" x-model="account.email" required maxlength="255" autocomplete="off"
                               @class(['field-input', 'field-input-error' => $field('email')])
                               @if ($field('email')) aria-invalid="true" aria-describedby="login-email-error" @endif>
                        @if ($field('email'))<p id="login-email-error" class="field-error">{{ $errors->first('email') }}</p>@endif
                    </div>
                    <div>
                        <label for="login-phone" class="field-label">Mobile number <span class="font-normal text-muted">(optional)</span></label>
                        <input id="login-phone" name="phone" type="tel" x-model="account.phone" placeholder="0917 123 4567"
                               @class(['field-input', 'field-input-error' => $field('phone')])
                               @if ($field('phone')) aria-invalid="true" aria-describedby="login-phone-error" @endif>
                        @if ($field('phone'))<p id="login-phone-error" class="field-error">{{ $errors->first('phone') }}</p>@endif
                    </div>
                </div>

                <fieldset class="border-t border-line pt-4">
                    <legend class="sr-only">Password</legend>
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm font-semibold text-ink">
                        <input type="checkbox" name="reset_password" value="1" x-model="reset"
                               class="h-5 w-5 rounded-sm border-line-strong text-primary">
                        Set a new password
                    </label>

                    <div x-show="reset" x-cloak class="mt-4">
                        <label for="login-new-password" class="field-label field-required">New password</label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input id="login-new-password" name="new_password" x-model="password" :type="show ? 'text' : 'password'"
                                       :disabled="! reset" required autocomplete="new-password"
                                       aria-describedby="login-new-password-hint{{ $field('new_password') ? ' login-new-password-error' : '' }}"
                                       @class(['field-input pr-12 font-mono', 'field-input-error' => $field('new_password')])
                                       @if ($field('new_password')) aria-invalid="true" @endif>
                                <button type="button" @click="show = ! show" :aria-pressed="show.toString()"
                                        class="absolute inset-y-0 right-0 flex min-w-16 items-center justify-center rounded-r-sm px-3 text-sm
                                               font-semibold text-primary hover:bg-primary-subtle">
                                    <span x-text="show ? 'Hide' : 'Show'">Show</span><span class="sr-only"> password</span>
                                </button>
                            </div>
                            <button type="button" class="btn-secondary min-h-11" @click="generate()">Generate</button>
                        </div>
                        <p id="login-new-password-hint" class="field-hint">
                            At least 12 characters, with upper- and lowercase letters, a number and a symbol. Give it to
                            the representative in person; they can change it under Account settings. Saving signs them out everywhere.
                        </p>
                        @if ($field('new_password'))<p id="login-new-password-error" class="field-error">{{ $errors->first('new_password') }}</p>@endif
                    </div>
                </fieldset>

                <div class="border-t border-line pt-6">
                    <x-admin-password-field id="login-admin-password" :error="$reopen ? $errors->first('admin_password') : null"
                                            purpose="Not the representative's. It confirms it is you before an account changes hands." />
                </div>
            </div>

            <div class="dialog-footer">
                <button type="button" class="btn-secondary" @click="close()">Cancel</button>
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </x-dialog>
</div>
