<x-layouts.cso header="Organization profile"
               subheader="Member names and contact details are visible to PESO administrators only, never on the public directory.">
    <form method="POST" action="{{ route('cso.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')

        <section class="panel p-5">
            <h2 class="font-semibold text-ink">Basic details</h2>
            <p class="mt-0.5 text-sm text-muted">All fields required unless marked optional.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="field-label">Organization name</label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name', $organization?->name) }}"
                           class="field-input @error('name') field-input-error @enderror"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror required>
                    @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="sector" class="field-label">Sector</label>
                    <select id="sector" name="sector"
                            class="field-input @error('sector') field-input-error @enderror" required>
                        <option value="">Select a sector</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector }}" @selected(old('sector', $organization?->sector) === $sector)>
                                {{ $sector }}
                            </option>
                        @endforeach
                    </select>
                    @error('sector')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="barangay" class="field-label">Barangay</label>
                    <select id="barangay" name="barangay"
                            class="field-input @error('barangay') field-input-error @enderror" required>
                        <option value="">Select a barangay</option>
                        @foreach ($barangays as $barangay)
                            <option value="{{ $barangay }}" @selected(old('barangay', $organization?->barangay) === $barangay)>
                                {{ $barangay }}
                            </option>
                        @endforeach
                    </select>
                    @error('barangay')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="advocacy" class="field-label">
                        Advocacy statement <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <textarea id="advocacy" name="advocacy" rows="4" class="field-input"
                              placeholder="What your organization works on and who it serves">{{ old('advocacy', $organization?->advocacy) }}</textarea>
                    <p class="field-hint">Shown on your public directory listing.</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="org_chart" class="field-label">
                        Organizational structure <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <textarea id="org_chart" name="org_chart" rows="4" class="field-input"
                              placeholder="One position per line, for example: President, Vice President, Secretary">{{ old('org_chart', $organization?->org_chart) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="logo" class="field-label">
                        Organization logo <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp"
                           class="field-input @error('logo') field-input-error @enderror">
                    <p class="field-hint">JPG, PNG, or WebP, up to 2 MB.</p>
                    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="panel p-5">
            <h2 class="font-semibold text-ink">Officers and members</h2>
            <p class="mt-0.5 text-sm text-muted">
                List your current officers. Leave a row blank to skip it.
            </p>

            @php
                $members = old('members', $organization?->members->toArray() ?? []);
                $rows = max(count($members), 5);
            @endphp

            <div class="mt-5 space-y-3">
                @for ($i = 0; $i < $rows; $i++)
                    <fieldset class="grid gap-3 sm:grid-cols-4">
                        <legend class="sr-only">Member {{ $i + 1 }}</legend>
                        <div>
                            <label for="member-name-{{ $i }}" class="field-label">Name</label>
                            <input type="text" id="member-name-{{ $i }}" name="members[{{ $i }}][name]"
                                   value="{{ $members[$i]['name'] ?? '' }}" class="field-input">
                        </div>
                        <div>
                            <label for="member-position-{{ $i }}" class="field-label">Position</label>
                            <input type="text" id="member-position-{{ $i }}" name="members[{{ $i }}][position]"
                                   value="{{ $members[$i]['position'] ?? '' }}" class="field-input">
                        </div>
                        <div>
                            <label for="member-contact-{{ $i }}" class="field-label">Contact number</label>
                            <input type="text" id="member-contact-{{ $i }}" name="members[{{ $i }}][contact_number]"
                                   value="{{ $members[$i]['contact_number'] ?? '' }}" class="field-input">
                        </div>
                        <div>
                            <label for="member-email-{{ $i }}" class="field-label">Email</label>
                            <input type="email" id="member-email-{{ $i }}" name="members[{{ $i }}][email]"
                                   value="{{ $members[$i]['email'] ?? '' }}" class="field-input">
                        </div>
                    </fieldset>
                @endfor
            </div>
        </section>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Save profile</button>
            <a href="{{ route('cso.dashboard') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-layouts.cso>
