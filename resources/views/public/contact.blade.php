<x-layouts.public title="Contact Us">
    <div class="mx-auto grid max-w-5xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Contact the Civil Society Desk Office</h1>
            <p class="mt-3 max-w-[60ch] text-muted">
                For questions about accreditation requirements, the status of a filed application, or
                help encoding a paper form.
            </p>

            <dl class="mt-8 space-y-5 text-sm">
                <div>
                    <dt class="font-semibold text-ink">Office</dt>
                    <dd class="mt-1 text-muted">
                        {{ config('office.office.name') }}, {{ config('office.office.parent') }}<br>
                        {{ config('office.office.address') }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-ink">Office hours</dt>
                    <dd class="mt-1 text-muted">{{ config('office.office.hours') }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-ink">Facebook</dt>
                    <dd class="mt-1">
                        <a href="{{ config('office.office.facebook') }}" rel="noopener" target="_blank"
                           class="text-navy-700 hover:underline">facebook.com/pesolingayen</a>
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-ink">Walk-in assistance</dt>
                    <dd class="mt-1 text-muted">
                        Staff can encode an application on your behalf if filling in the online form is
                        difficult. Bring your printed requirements.
                    </dd>
                </div>
            </dl>
        </div>

        <div class="panel h-fit p-6">
            <h2 class="font-semibold text-ink">Send a message</h2>
            <p class="mt-1 text-sm text-muted">All fields required unless marked optional.</p>

            <form class="mt-6 space-y-5" method="POST" action="{{ route('contact.send') }}">
                @csrf
                <div>
                    <label for="name" class="field-label">Your name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           class="field-input @error('name') field-input-error @enderror"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror required>
                    @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="field-label">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="field-input @error('email') field-input-error @enderror"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror required>
                    @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="organization" class="field-label">
                        Organization <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <input type="text" id="organization" name="organization" value="{{ old('organization') }}"
                           class="field-input">
                </div>

                <div>
                    <label for="message" class="field-label">Message</label>
                    <textarea id="message" name="message" rows="5"
                              class="field-input @error('message') field-input-error @enderror"
                              @error('message') aria-invalid="true" aria-describedby="message-error" @enderror required>{{ old('message') }}</textarea>
                    @error('message')<p id="message-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary w-full">Send message</button>
            </form>
        </div>
    </div>
</x-layouts.public>
