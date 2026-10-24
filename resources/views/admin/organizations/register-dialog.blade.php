{{--
    "Register an organization" dialog (560px). Included on Applications, Organizations and Accounts;
    any button that dispatches open-dialog 'register-organization' opens it, and so does ?register=1
    (where the old Register page now points). After a validation error
    the page comes back with the dialog open and the typed values kept.
--}}
@php
    $reopen = $errors->any() && old('_dialog') === 'register-organization';
    $login = $reopen && old('create_login');
    $field = fn (string $name) => $reopen && $errors->has($name);
@endphp

<x-dialog name="register-organization" title="Register an organization" width="sm" :open="$reopen || request()->boolean('register')"
          subtitle="For walk-in applicants and records the office already holds.">
    <form method="POST" action="{{ route('admin.organizations.store') }}" class="flex min-h-0 flex-1 flex-col"
          x-data="{ login: @js((bool) $login) }">
        @csrf
        <input type="hidden" name="_dialog" value="register-organization">

        <div class="dialog-body space-y-6">
            <p class="text-sm text-muted"><span class="text-danger" aria-hidden="true">*</span> Required</p>

            <div>
                <label for="reg-name" class="field-label field-required">Organization name</label>
                <input id="reg-name" name="name" autofocus value="{{ $reopen ? old('name') : '' }}" required maxlength="255" autocomplete="off"
                       placeholder="e.g. Samahan ng Kababaihan ng Libsong"
                       @class(['field-input', 'field-input-error' => $field('name')])
                       @if ($field('name')) aria-invalid="true" aria-describedby="reg-name-error" @endif>
                @if ($field('name'))<p id="reg-name-error" class="field-error">{{ $errors->first('name') }}</p>@endif
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div x-data="combobox(@js(array_values(config('barangays'))), @js($reopen ? (string) old('barangay') : ''))"
                     class="relative" @focusout="settle()">
                    <label for="reg-barangay" class="field-label field-required">Barangay</label>
                    <input id="reg-barangay" name="barangay" x-ref="input" x-model="query" required autocomplete="off"
                           role="combobox" aria-autocomplete="list" aria-controls="reg-barangay-list"
                           :aria-expanded="expanded.toString()"
                           :aria-activedescendant="expanded && matches[active] ? 'reg-barangay-' + active : null"
                           @focus="show()" @click="show()" @input="expanded = true; active = 0"
                           @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter="chooseActive($event)"
                           @keydown.escape="if (expanded) { expanded = false; $event.stopPropagation() }"
                           placeholder="Type to search"
                           @class(['field-input pr-12', 'field-input-error' => $field('barangay')])
                           @if ($field('barangay')) aria-invalid="true" aria-describedby="reg-barangay-error" @endif>
                    <x-phosphor-caret-up-down class="pointer-events-none absolute right-4 top-11 h-5 w-5 text-muted" aria-hidden="true" />
                    <ul id="reg-barangay-list" role="listbox" aria-label="Barangays" x-ref="listbox" x-show="expanded" x-cloak class="listbox">
                        <template x-for="(option, i) in matches" :key="option">
                            <li role="option" :id="'reg-barangay-' + i" :aria-selected="(option === query).toString()"
                                :data-active="i === active ? '' : null" @mousedown.prevent="choose(option)" @mousemove="active = i"
                                class="listbox-option">
                                <span x-text="option"></span>
                                <x-phosphor-check x-show="option === query" class="ml-auto h-4 w-4 text-primary" aria-hidden="true" />
                            </li>
                        </template>
                        <li x-show="! matches.length" class="px-3 py-2 text-sm text-muted">No barangay matches that.</li>
                    </ul>
                    @if ($field('barangay'))<p id="reg-barangay-error" class="field-error">{{ $errors->first('barangay') }}</p>@endif
                </div>

                <div>
                    <label for="reg-sector" class="field-label field-required">Sector</label>
                    <select id="reg-sector" name="sector" required
                            @class(['field-input', 'field-input-error' => $field('sector')])
                            @if ($field('sector')) aria-invalid="true" aria-describedby="reg-sector-error" @endif>
                        <option value="" disabled @selected(! ($reopen && old('sector')))>Choose a sector</option>
                        @foreach (config('sectors') as $sector)
                            <option @selected($reopen && old('sector') === $sector)>{{ $sector }}</option>
                        @endforeach
                    </select>
                    @if ($field('sector'))<p id="reg-sector-error" class="field-error">{{ $errors->first('sector') }}</p>@endif
                </div>
            </div>

            <div>
                <label for="reg-advocacy" class="field-label">Advocacy <span class="font-normal text-muted">(optional)</span></label>
                <textarea id="reg-advocacy" name="advocacy" rows="2" maxlength="2000" class="field-input"
                          placeholder="What the organization works on">{{ $reopen ? old('advocacy') : '' }}</textarea>
                @if ($field('advocacy'))<p class="field-error">{{ $errors->first('advocacy') }}</p>@endif
            </div>

            <fieldset class="border-t border-line pt-4">
                <legend class="sr-only">Representative</legend>
                <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm font-semibold text-ink">
                    <input type="checkbox" name="create_login" value="1" x-model="login"
                           class="h-5 w-5 rounded-sm border-line-strong text-primary">
                    Create a login for the representative
                </label>

                <div x-show="login" x-cloak class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="reg-rep-name" class="field-label field-required">Full name</label>
                        <input id="reg-rep-name" name="rep_name" value="{{ $reopen ? old('rep_name') : '' }}" :disabled="! login" required
                               maxlength="255" autocomplete="off" placeholder="Juan dela Cruz"
                               @class(['field-input', 'field-input-error' => $field('rep_name')])
                               @if ($field('rep_name')) aria-invalid="true" aria-describedby="reg-rep-name-error" @endif>
                        @if ($field('rep_name'))<p id="reg-rep-name-error" class="field-error">{{ $errors->first('rep_name') }}</p>@endif
                    </div>
                    <div>
                        <label for="reg-rep-email" class="field-label field-required">Email</label>
                        <input id="reg-rep-email" name="rep_email" type="email" value="{{ $reopen ? old('rep_email') : '' }}" :disabled="! login" required
                               maxlength="255" autocomplete="off" placeholder="name@example.com"
                               aria-describedby="reg-rep-email-hint{{ $field('rep_email') ? ' reg-rep-email-error' : '' }}"
                               @class(['field-input', 'field-input-error' => $field('rep_email')])
                               @if ($field('rep_email')) aria-invalid="true" @endif>
                        @if ($field('rep_email'))<p id="reg-rep-email-error" class="field-error">{{ $errors->first('rep_email') }}</p>@endif
                    </div>
                    <p id="reg-rep-email-hint" class="field-hint mt-0 sm:col-span-2">A password-setup link will be sent to the email.</p>
                </div>
            </fieldset>
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-secondary" @click="close()">Cancel</button>
            <button type="submit" class="btn-primary">Register organization</button>
        </div>
    </form>
</x-dialog>
