{{-- Application type and requirement uploads, shared by the CSO form and the admin "File an application"
     dialog. No card of its own, so it sits in either. Documents marked new_only (constitution and
     by-laws) appear only for a new application. The official receipt is hidden when the fee is waived
     (ticked by default while a free filing period is open). --}}
@php
    $openPeriod = \App\Models\FeeFreePeriod::open();
    $fees = config('office.fees');
@endphp
<div x-data="{ type: @js(old('type', $hasBeenAccredited ? 'renewal' : 'new')), waived: @js((bool) old('fee_waived', $openPeriod !== null)) }">
    <fieldset>
        <legend class="field-label">Application type</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'new' => ['New', 'Not accredited before'],
                'renewal' => ['Renewal', 'Renewing an existing accreditation'],
            ] as $value => [$title, $description])
                <label class="flex cursor-pointer items-start gap-3 rounded-md border border-line-strong p-4
                              transition-colors duration-fast hover:border-primary/40
                              has-[:checked]:border-primary has-[:checked]:bg-primary-subtle">
                    <input type="radio" name="type" value="{{ $value }}" class="mt-1" x-model="type"
                           @checked(old('type', $hasBeenAccredited ? 'renewal' : 'new') === $value) required>
                    <span>
                        <span class="block text-sm font-semibold text-ink">{{ $title }}</span>
                        <span class="block text-sm text-muted">{{ $description }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('type')<p class="field-error">{{ $message }}</p>@enderror
    </fieldset>

    <div class="mt-6">
        <p class="field-label">Accreditation fee</p>
        <label class="flex items-start gap-3 text-sm">
            <input type="hidden" name="fee_waived" value="0">
            <input type="checkbox" name="fee_waived" value="1" x-model="waived" class="mt-hair h-4 w-4 shrink-0 rounded-sm border-line-strong text-primary">
            <span>
                <span class="font-semibold text-ink">No fee: filed within a free period</span>
                <span class="block text-muted">
                    @if ($openPeriod)
                        Open now under {{ $openPeriod->memorandum }}, until {{ $openPeriod->ends_on->format('j F Y') }}. Accredited for {{ config('office.validity_years.free') }} years.
                    @else
                        No free period is open. Untick unless the office waives the fee; the receipt
                        (PHP {{ number_format($fees['new']) }} new, PHP {{ number_format($fees['renewal']) }} renewal) is then required.
                    @endif
                </span>
            </span>
        </label>
    </div>

    <h3 class="mt-6 text-sm font-semibold text-ink">Requirements</h3>
    <p class="mt-1 text-sm text-muted">PDF, JPG or PNG, up to 5 MB each. Add an expiry date if the document shows one.</p>

    <div class="mt-2 divide-y divide-line border-t border-line">
        @foreach ($documentTypes as $key => $type)
            <div class="grid gap-3 py-4 sm:grid-cols-[minmax(0,1fr)_11rem]"
                 @if (! empty($type['new_only'])) x-show="type === 'new'" @endif
                 @if ($key === 'fee_receipt') x-show="! waived" @endif>
                @php
                    // "DOLE or SEC Certification" reads as "the DOLE or SEC certification": lowercase all but acronyms.
                    $lowerLabel = preg_replace_callback('/\b[A-Za-z][a-z]+\b/', fn ($m) => strtolower($m[0]), $type['label']);
                @endphp
                <div x-data="documentPrecheck(@js($type['checks'] ?? []))" class="min-w-0">
                    <label for="doc-{{ $key }}" class="field-label">
                        {{ $type['label'] }}
                        @if ($type['optional'])
                            <span class="font-normal text-muted">(optional)</span>
                        @endif
                    </label>
                    <x-file-field>
                        <input type="file" id="doc-{{ $key }}" name="documents[{{ $key }}]"
                               accept=".pdf,.jpg,.jpeg,.png" @change="check($event)"
                               aria-describedby="doc-{{ $key }}-hint doc-{{ $key }}-check @error("documents.{$key}") doc-{{ $key }}-error @enderror"
                               class="field-file @error("documents.{$key}") field-input-error @enderror"
                               @error("documents.{$key}") aria-invalid="true" @enderror
                               @if (! empty($type['new_only'])) :disabled="type !== 'new'" @endif
                               @if ($key === 'fee_receipt') :disabled="waived" @endif
                               @required(! $type['optional'])>
                    </x-file-field>
                    <input type="hidden" name="ocr_text[{{ $key }}]" :value="text">
                    <p id="doc-{{ $key }}-hint" class="field-hint">{{ config("office.requirement_notes.{$key}") }}</p>

                    {{-- Advisory only: never blocks submitting. PESO sees the server's own verdict. --}}
                    <p id="doc-{{ $key }}-check" aria-live="polite" x-cloak x-show="state !== 'idle'"
                       class="mt-2 flex items-start gap-tight text-sm"
                       :class="{
                           'text-muted': state === 'reading' || state === 'failed',
                           'text-success': state === 'matched',
                           'text-warning': state === 'mismatch' || state === 'unreadable',
                       }">
                        <span x-show="state === 'reading'">Reading the document. You can keep filling in the form.</span>
                        <span x-show="state === 'matched'">This looks like the {{ $lowerLabel }}.</span>
                        <span x-show="state === 'mismatch'">
                            This may not be the {{ $lowerLabel }}: we couldn't find
                            <span x-text="missing.join('; ')"></span> in it. Check that you chose the right file.
                            You can still submit it; the Desk Office will review it.
                        </span>
                        <span x-show="state === 'unreadable'">
                            Very little text could be read, which happens with handwriting or blurry photos.
                            You can still submit it; the Desk Office will review it.
                        </span>
                        <span x-show="state === 'failed'">The document could not be checked here. You can still submit it.</span>
                    </p>
                    @error("documents.{$key}")
                        <p id="doc-{{ $key }}-error" class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="exp-{{ $key }}" class="field-label">
                        Expires <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <input type="date" id="exp-{{ $key }}" name="expires_at[{{ $key }}]"
                           value="{{ old("expires_at.{$key}") }}"
                           class="field-input @error("expires_at.{$key}") field-input-error @enderror">
                    @error("expires_at.{$key}")<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        @endforeach
    </div>
</div>
