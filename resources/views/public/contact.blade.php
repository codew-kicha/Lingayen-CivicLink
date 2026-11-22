<x-layouts.public title="Contact Us">
    <x-page-banner title="Contact the Civil Society Desk Office" photo="events/cso7.jpg"
                   alt="Representatives of the PWD Association at a CSO assembly in Lingayen"
                   lede="For questions about accreditation requirements, the status of a filed application, or help encoding a paper form." />

    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:px-8 lg:py-section">
        {{-- The ways to reach the office, each one a direct action (call, write, find, message). --}}
        <div>
            <h2 class="section-title">Visit or call</h2>
            <p class="mt-4 max-w-[52ch] leading-relaxed text-muted">
                {{ config('office.office.name') }}, {{ config('office.office.parent') }}. {{ config('office.office.hours') }}.
            </p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['phosphor-phone', 'Call the office', config('office.office.phone'), 'tel:'.config('office.office.phone_link'), false],
                    ['phosphor-envelope-simple', 'Send an email', config('office.office.email'), 'mailto:'.config('office.office.email'), false],
                    ['phosphor-map-pin', 'Visit', config('office.office.address'), null, false],
                    ['phosphor-facebook-logo', 'Message on Facebook', 'facebook.com/pesolingayen', config('office.office.facebook'), true],
                ] as [$icon, $label, $value, $href, $external])
                    <li>
                        @if ($href)
                            <a href="{{ $href }}" @if ($external) target="_blank" rel="noopener" @endif
                               class="group flex h-full gap-4 rounded-lg border border-line bg-paper p-6 transition duration-base ease-out-strong hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-panel">
                        @else
                            <div class="flex h-full gap-4 rounded-lg border border-line bg-paper p-6">
                        @endif
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700 transition-colors duration-base group-hover:bg-brand-800 group-hover:text-accent-400">
                                <x-dynamic-component :component="$icon" class="h-6 w-6" aria-hidden="true" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-ink">{{ $label }}</span>
                                <span class="mt-1 block break-words text-sm text-muted @if ($href) group-hover:text-brand-800 @endif">{{ $value }}</span>
                            </span>
                        @if ($href)
                            </a>
                        @else
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 flex gap-4 rounded-lg bg-brand-900 p-6 text-brand-100">
                <x-phosphor-hand-heart class="h-6 w-6 shrink-0 text-accent-400" aria-hidden="true" />
                <p class="text-sm leading-relaxed">
                    <span class="font-semibold text-paper">Walk-in assistance.</span> Staff can encode an application on your
                    behalf if filling in the online form is difficult. Bring your printed requirements.
                </p>
            </div>
            <figure class="mt-4 hidden overflow-hidden rounded-lg lg:block">
                <img src="{{ asset('images/events/cso6.jpg') }}" alt="CSO representatives at an assembly in Lingayen" loading="lazy"
                     width="1200" height="800" class="aspect-[16/9] w-full object-cover">
            </figure>
        </div>

        <div class="panel h-fit p-6 sm:p-8 lg:sticky lg:top-24">
            <h2 class="font-serif text-xl font-bold text-brand-900">Send a message</h2>
            <p class="mt-1 text-sm text-muted">
                It goes to the Civil Society Desk Office's email, and they reply to the address you give.
            </p>

            <div class="mt-4">
                <x-flash />
            </div>

            <form class="mt-2 space-y-6" method="POST" action="{{ route('contact.send') }}">
                @csrf
                <p class="text-sm text-muted"><span class="text-danger" aria-hidden="true">*</span> Required</p>

                <div>
                    <label for="name" class="field-label field-required">Your name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="255"
                           class="field-input @error('name') field-input-error @enderror"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror required>
                    @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="field-label field-required">Your email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255"
                           placeholder="name@example.com"
                           aria-describedby="email-hint{{ $errors->has('email') ? ' email-error' : '' }}"
                           class="field-input @error('email') field-input-error @enderror"
                           @error('email') aria-invalid="true" @enderror required>
                    <p id="email-hint" class="field-hint">Your own email, so the office can write back to you.</p>
                    @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="organization" class="field-label">
                        Organization <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <input type="text" id="organization" name="organization" value="{{ old('organization') }}" maxlength="255"
                           class="field-input">
                </div>

                <div>
                    <label for="message" class="field-label field-required">Message</label>
                    <textarea id="message" name="message" rows="5" maxlength="2000"
                              class="field-input @error('message') field-input-error @enderror"
                              @error('message') aria-invalid="true" aria-describedby="message-error" @enderror required>{{ old('message') }}</textarea>
                    @error('message')<p id="message-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary w-full">Send message</button>
            </form>
        </div>
    </div>
</x-layouts.public>
