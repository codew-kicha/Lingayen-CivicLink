{{--
    Step 4: the Sangguniang Bayan's accreditation form, filled in here instead of downloaded (pre-final
    defense panel). Answers start from the profile and the account; "Preview" shows the form exactly as
    the system will produce it when the application is submitted.
--}}
@php
    $form = $draft->form_data ?? [];
    $value = fn (string $field) => old($field, $form[$field] ?? '');
    $type = old('type', $draft->type);
    // Drafts started before the barangay list fall back to the organization's own barangay.
    $placeBarangay = old('place_barangay', $form['place_barangay'] ?? $organization->barangay);
    $placeDetail = old('place_detail', $form['place_detail'] ?? '');
@endphp

<x-application-frame :draft="$draft" :step="$step" :done="$done">
    <form method="POST" action="{{ route('cso.application.form') }}" class="card" x-data="{ type: @js($type) }">
        @csrf
        <input type="hidden" name="step" value="form">

        <div class="flex flex-wrap items-start gap-3 border-b border-line px-6 py-4">
            <div class="min-w-0 flex-1 basis-72">
                <h2 class="card-title">Accreditation form</h2>
                <p class="mt-1 max-w-[72ch] text-sm text-muted">
                    The same form the Sangguniang Bayan uses. Check the details, certify them, and the completed form is
                    attached to your application when you submit.
                </p>
            </div>
            @if (! empty($form['certified_at']))
                <a href="{{ route('cso.application.preview') }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">
                    <x-phosphor-file-pdf class="h-4 w-4" aria-hidden="true" />Preview the form
                </a>
            @endif
        </div>

        <div class="space-y-6 p-6">
            <fieldset>
                <legend class="field-label">Type of application</legend>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach (['new' => ['For New Application', 'Not accredited before'], 'renewal' => ['For Renewal', 'Renewing an accreditation that is ending or has ended']] as $option => [$title, $description])
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-line-strong p-4 transition-colors duration-fast
                                      hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary-subtle">
                            <input type="radio" name="type" value="{{ $option }}" class="mt-1" x-model="type" @checked($type === $option) required>
                            <span>
                                <span class="block text-sm font-semibold text-ink">{{ $title }}</span>
                                <span class="block text-sm text-muted">{{ $description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="field-hint" x-show="type === 'new'">A new application also asks for your Constitution and By-Laws in the next step.</p>
                {{-- Free while a DILG free period is open; otherwise the fee applies after the form is approved. --}}
                <x-fee-period-note always class="mt-3" />
            </fieldset>

            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ([
                    'organization_name' => ['Name of Organization', 'organization'],
                    'representative_name' => ['Name of Representative', 'name'],
                    'contact_number' => ['Contact No. of Representative', 'tel'],
                ] as $field => [$label, $autocomplete])
                    <div>
                        <label for="{{ $field }}" class="field-label">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}" required maxlength="255" autocomplete="{{ $autocomplete }}"
                               @if ($field === 'representative_name') pattern="{{ \App\Rules\PersonName::PATTERN }}" data-person-name="{{ \App\Rules\PersonName::MESSAGE }}" @endif
                               @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                               class="field-input @error($field) field-input-error @enderror">
                        @error($field)<p id="{{ $field }}-error" class="field-error">{{ $message }}</p>@enderror
                        @if ($field === 'representative_name')<p class="field-error" data-person-name-error hidden aria-live="polite"></p>@endif
                    </div>
                @endforeach
            </div>

            {{-- Place of Operation: the barangay from Lingayen's list, plus the specific place within it. --}}
            <fieldset x-data="{ barangay: @js($placeBarangay), detail: @js($placeDetail) }">
                <legend class="field-label">Place of Operation</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="place_barangay" class="text-sm text-muted">Barangay</label>
                        <select id="place_barangay" name="place_barangay" x-model="barangay" required
                                @error('place_barangay') aria-invalid="true" aria-describedby="place_barangay-error" @enderror
                                class="field-input @error('place_barangay') field-input-error @enderror">
                            <option value="">Choose a barangay</option>
                            @foreach (config('barangays') as $name)
                                <option value="{{ $name }}" @selected($placeBarangay === $name)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('place_barangay')<p id="place_barangay-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="place_detail" class="text-sm text-muted">Specific place <span class="font-normal">(optional)</span></label>
                        <input id="place_detail" name="place_detail" x-model="detail" maxlength="150" autocomplete="street-address"
                               placeholder="e.g. Purok 3, beside the barangay hall"
                               @error('place_detail') aria-invalid="true" aria-describedby="place_detail-error" @enderror
                               class="field-input @error('place_detail') field-input-error @enderror">
                        @error('place_detail')<p id="place_detail-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="field-hint" x-show="barangay" x-cloak>
                    On the form: <span class="font-semibold text-ink" x-text="(detail.trim() ? detail.trim() + ', ' : '') + 'Barangay ' + barangay + ', Lingayen, Pangasinan'"></span>
                </p>
            </fieldset>

            <div class="rounded-md border border-line bg-canvas p-4">
                <p class="text-sm font-semibold text-ink">Attached automatically</p>
                <ul class="mt-2 space-y-1 text-sm text-muted">
                    <li class="flex gap-2"><x-phosphor-check class="mt-hair h-4 w-4 shrink-0 text-success" aria-hidden="true" />This form, completed with your answers</li>
                    <li class="flex gap-2"><x-phosphor-check class="mt-hair h-4 w-4 shrink-0 text-success" aria-hidden="true" />The list of officers and members from step 3 ({{ $organization->members()->count() }} people)</li>
                </ul>
            </div>

            <div class="space-y-4 border-t border-line pt-6">
                <label for="certify" class="flex items-start gap-3 text-sm text-ink">
                    <input id="certify" name="certify" type="checkbox" value="1" required @checked(old('certify', ! empty($form['certified_at'])))
                           class="mt-hair h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary"
                           @error('certify') aria-invalid="true" aria-describedby="certify-error" @enderror>
                    <span>I certify that the information in this form is true and correct, and I am authorized to apply on behalf of the organization.</span>
                </label>
                @error('certify')<p id="certify-error" class="field-error">{{ $message }}</p>@enderror

                <div class="max-w-md">
                    <label for="certified_name" class="field-label">Your full name, as your signature</label>
                    <input id="certified_name" name="certified_name" value="{{ old('certified_name', $form['certified_name'] ?? auth()->user()->name) }}" required maxlength="255"
                           pattern="{{ \App\Rules\PersonName::PATTERN }}" data-person-name="{{ \App\Rules\PersonName::MESSAGE }}"
                           @error('certified_name') aria-invalid="true" aria-describedby="certified_name-error" @enderror
                           class="field-input @error('certified_name') field-input-error @enderror">
                    @error('certified_name')<p id="certified_name-error" class="field-error">{{ $message }}</p>@enderror
                    <p class="field-error" data-person-name-error hidden aria-live="polite"></p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-line px-6 py-4">
            <a href="{{ route('cso.application.step', 'members') }}" class="btn-ghost">
                <x-phosphor-arrow-left class="h-4 w-4" aria-hidden="true" />Back
            </a>
            <button type="submit" name="action" value="next" class="btn-primary ml-auto">
                Save and continue <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </form>
</x-application-frame>
