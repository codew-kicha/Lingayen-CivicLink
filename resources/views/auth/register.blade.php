<x-guest-layout>
    <x-slot:title>Register your organization</x-slot:title>
    <x-slot:heading>Register your organization</x-slot:heading>
    <x-slot:intro>This creates your organization's record and your account as its representative. One account per organization.</x-slot:intro>

    <x-slot:aside>
        <p class="text-sm font-semibold text-rose-400">What happens next</p>
        <ol class="mt-5 space-y-5">
            @foreach ([
                ['Confirm your email', 'We send a link to the address you register with.'],
                ['Upload your requirements', 'Accreditation form, officers list, constitution and by-laws, and fee receipt.'],
                ['Desk Office review', 'Staff check your documents, then the Sangguniang Bayan deliberates.'],
                ['Track every stage', 'Your dashboard shows each reading as it happens. No phone calls needed.'],
            ] as [$step, $detail])
                <li class="grid grid-cols-[2rem_minmax(0,1fr)] gap-x-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-navy-600 font-mono text-sm text-navy-200">{{ $loop->iteration }}</span>
                    <div>
                        <p class="font-semibold">{{ $step }}</p>
                        <p class="mt-0.5 text-sm text-navy-200">{{ $detail }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
        <a href="{{ route('accreditation') }}" class="mt-8 inline-block text-sm font-semibold text-paper underline underline-offset-4 hover:text-rose-200">
            Read the full accreditation guidelines
        </a>
    </x-slot:aside>

    <form method="POST" action="{{ route('register') }}" class="space-y-8">
        @csrf

        <fieldset class="space-y-5">
            <legend class="font-semibold text-ink">Your organization</legend>

            <div>
                <label for="organization_name" class="field-label">Organization name</label>
                <input id="organization_name" name="organization_name" value="{{ old('organization_name') }}" required autofocus
                       @error('organization_name') aria-invalid="true" aria-describedby="organization_name-error" @enderror
                       class="field-input @error('organization_name') field-input-error @enderror">
                @error('organization_name')<p id="organization_name-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="barangay" class="field-label">Barangay</label>
                    <select id="barangay" name="barangay" required class="field-input @error('barangay') field-input-error @enderror">
                        <option value="">Choose one</option>
                        @foreach ($barangays as $barangay)
                            <option @selected(old('barangay') === $barangay)>{{ $barangay }}</option>
                        @endforeach
                    </select>
                    @error('barangay')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sector" class="field-label">Sector</label>
                    <select id="sector" name="sector" required class="field-input @error('sector') field-input-error @enderror">
                        <option value="">Choose one</option>
                        @foreach ($sectors as $sector)
                            <option @selected(old('sector') === $sector)>{{ $sector }}</option>
                        @endforeach
                    </select>
                    @error('sector')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </fieldset>

        <div class="border-t border-line pt-8">
        <fieldset class="space-y-5">
            <legend class="font-semibold text-ink">You, as its representative</legend>

            <div>
                <label for="name" class="field-label">Full name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                       @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                       class="field-input @error('name') field-input-error @enderror">
                @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                           class="field-input @error('email') field-input-error @enderror">
                    @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="field-label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" placeholder="0917 123 4567"
                           aria-describedby="phone-hint @error('phone') phone-error @enderror"
                           @error('phone') aria-invalid="true" @enderror
                           class="field-input @error('phone') field-input-error @enderror">
                    <p id="phone-hint" class="field-hint">For reminders about your application.</p>
                    @error('phone')<p id="phone-error" class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <x-password-field autocomplete="new-password" checklist />
            <x-password-field name="password_confirmation" label="Type the password again" autocomplete="new-password" />
        </fieldset>
        </div>

        <button type="submit" class="btn-primary w-full">Register organization</button>

        <p class="text-center text-sm text-muted">
            Already registered? <a href="{{ route('login') }}" class="font-semibold text-navy-700 hover:underline">Sign in</a>
        </p>
    </form>
</x-guest-layout>
