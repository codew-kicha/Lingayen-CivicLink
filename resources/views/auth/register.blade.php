{{--
    Sign-up in two short steps: the organization, then its representative. Both steps are one form
    (the hidden step stays in the page), so a single POST sends everything. After a validation error
    the page reopens on the step that holds the first problem. Without JavaScript both steps show.
--}}
@php
    $orgFields = ['organization_name', 'barangay', 'sector'];
    $startStep = $errors->hasAny($orgFields) || ! $errors->any() ? 1 : 2;
@endphp

<x-guest-layout>
    <x-slot:title>Register your organization</x-slot:title>
    <x-slot:heading>Register your organization</x-slot:heading>

    <form method="POST" action="{{ route('register') }}"
          x-data="{
              step: {{ $startStep }},
              next() {
                  for (const field of this.$refs.organization.querySelectorAll('input:not([type=hidden]), select')) {
                      if (! field.checkValidity()) { field.reportValidity(); return; }
                  }
                  this.step = 2;
                  this.$nextTick(() => document.getElementById('name').focus());
              },
              back() {
                  this.step = 1;
                  this.$nextTick(() => document.getElementById('organization_name').focus());
              },
          }"
          @submit="if (step === 1) { $event.preventDefault(); next(); }">
        @csrf

        {{-- Where you are: a short label and a two-part bar. --}}
        <div class="mb-4" x-cloak>
            <p class="text-sm font-semibold text-ink" aria-live="polite">
                <span x-text="step === 1 ? 'Step 1 of 2: Your organization' : 'Step 2 of 2: You, as its representative'">Step {{ $startStep }} of 2</span>
            </p>
            <div class="mt-2 grid grid-cols-2 gap-1" aria-hidden="true">
                <span class="h-1 rounded-full bg-brand-700"></span>
                <span class="h-1 rounded-full transition-colors duration-base" :class="step === 2 ? 'bg-brand-700' : 'bg-line'"></span>
            </div>
        </div>

        <fieldset x-ref="organization" x-show="step === 1" @if ($startStep !== 1) x-cloak @endif class="space-y-4" aria-label="Your organization">

            <div>
                <label for="organization_name" class="field-label">Organization name</label>
                <input id="organization_name" name="organization_name" value="{{ old('organization_name') }}" required maxlength="255"
                       @if ($startStep === 1) autofocus @endif placeholder="e.g. Libsong Senior Citizens Association"
                       @error('organization_name') aria-invalid="true" aria-describedby="organization_name-error" @enderror
                       class="field-input @error('organization_name') field-input-error @enderror">
                @error('organization_name')<p id="organization_name-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <x-barangay-combobox :value="old('barangay')" :error="$errors->first('barangay') ?: null" :marked="false" />

            <div>
                <label for="sector" class="field-label">Sector</label>
                <select id="sector" name="sector" required
                        @error('sector') aria-invalid="true" aria-describedby="sector-error" @enderror
                        class="field-input @error('sector') field-input-error @enderror">
                    <option value="" disabled @selected(! old('sector'))>Choose a sector</option>
                    @foreach ($sectors as $sector)
                        <option @selected(old('sector') === $sector)>{{ $sector }}</option>
                    @endforeach
                </select>
                @error('sector')<p id="sector-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <button type="button" class="btn-primary w-full" x-cloak @click="next()">
                Next <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
            </button>
        </fieldset>

        <fieldset x-show="step === 2" @if ($startStep !== 2) x-cloak @endif class="space-y-4" aria-label="You, as its representative">

            <div>
                <label for="name" class="field-label">Full name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" maxlength="255"
                       @if ($startStep === 2) autofocus @endif
                       @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                       class="field-input @error('name') field-input-error @enderror">
                @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" maxlength="255"
                       placeholder="name@example.com"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="field-input @error('email') field-input-error @enderror">
                @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="field-label">Mobile number</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" placeholder="0917 123 4567"
                       @error('phone') aria-describedby="phone-error" @enderror
                       @error('phone') aria-invalid="true" @enderror
                       class="field-input @error('phone') field-input-error @enderror">
                @error('phone')<p id="phone-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <x-password-field autocomplete="new-password" checklist />

            <div>
                <label for="privacy_consent" class="flex items-start gap-3 text-sm text-ink">
                    <input id="privacy_consent" name="privacy_consent" type="checkbox" value="1" required @checked(old('privacy_consent'))
                           class="mt-hair h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary"
                           @error('privacy_consent') aria-invalid="true" aria-describedby="privacy_consent-error" @enderror>
                    <span>I have read the <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="font-semibold text-primary underline underline-offset-2">privacy notice</a>
                        and agree to the Desk Office processing my and my organization's information for accreditation.</span>
                </label>
                @error('privacy_consent')<p id="privacy_consent-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-secondary" x-cloak @click="back()">
                    <x-phosphor-arrow-left class="h-4 w-4" aria-hidden="true" />Back
                </button>
                <button type="submit" class="btn-primary flex-1">Register organization</button>
            </div>
        </fieldset>
    </form>

    <x-slot:footer>
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Sign in</a>
    </x-slot:footer>
</x-guest-layout>
