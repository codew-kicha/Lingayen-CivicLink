{{-- Application type and requirement uploads, shared by the CSO form and PESO assisted encoding. --}}
<section class="panel p-6">
    <h2 class="font-semibold text-ink">Application type</h2>

    <fieldset class="mt-4">
        <legend class="sr-only">Application type</legend>
        <div class="space-y-2">
            @foreach ([
                'new' => 'New accreditation, this organization has not been accredited before',
                'renewal' => 'Renewal of an existing accreditation',
            ] as $value => $label)
                <label class="flex items-start gap-3 rounded-sm border border-line-strong p-3
                              hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary-subtle">
                    <input type="radio" name="type" value="{{ $value }}" class="mt-1"
                           @checked(old('type', $hasActiveAccreditation ? 'renewal' : 'new') === $value) required>
                    <span class="text-sm text-ink">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('type')<p class="field-error">{{ $message }}</p>@enderror
    </fieldset>
</section>

<section class="panel p-6">
    <h2 class="font-semibold text-ink">Requirements</h2>
    <p class="mt-1 text-sm text-muted">
        Upload each required document. Add an expiry date where the document carries one, so
        PESO can track when it needs replacing. Each file is read in your browser as you choose it, to
        catch a document attached in the wrong slot.
    </p>

    <div class="mt-6 space-y-6">
        @foreach ($documentTypes as $key => $type)
            <div class="grid gap-3 border-t border-line pt-6 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <div x-data="documentPrecheck(@js($type['keywords'] ?? []), {{ $type['min_matches'] ?? 1 }})">
                    <label for="doc-{{ $key }}" class="field-label">
                        {{ $type['label'] }}
                        @if ($type['optional'])
                            <span class="font-normal text-muted">(optional, only if you have one)</span>
                        @endif
                    </label>
                    <input type="file" id="doc-{{ $key }}" name="documents[{{ $key }}]"
                           accept=".pdf,.jpg,.jpeg,.png" @change="check($event)"
                           aria-describedby="doc-{{ $key }}-check @error("documents.{$key}") doc-{{ $key }}-error @enderror"
                           class="field-input @error("documents.{$key}") field-input-error @enderror"
                           @error("documents.{$key}") aria-invalid="true" @enderror
                           @required(! $type['optional'])>
                    <input type="hidden" name="ocr_text[{{ $key }}]" :value="text">
                    <p class="field-hint">{{ config("office.requirement_notes.{$key}") }}</p>

                    {{-- Advisory only: never blocks submitting. PESO sees the server's own verdict. --}}
                    <p id="doc-{{ $key }}-check" aria-live="polite" x-cloak x-show="state !== 'idle'"
                       class="mt-2 flex items-start gap-tight text-sm"
                       :class="{
                           'text-muted': state === 'reading' || state === 'failed',
                           'text-success': state === 'matched',
                           'text-warning': state === 'mismatch' || state === 'unreadable',
                       }">
                        <span x-show="state === 'reading'">Reading the document. You can keep filling in the form.</span>
                        <span x-show="state === 'matched'">This looks like the {{ Str::lower($type['label']) }}.</span>
                        <span x-show="state === 'mismatch'">
                            This doesn't look like the {{ Str::lower($type['label']) }}. Check that you chose the right file.
                            You can still submit it; PESO will review it.
                        </span>
                        <span x-show="state === 'unreadable'">
                            Very little text could be read, which happens with handwriting or blurry photos.
                            You can still submit it; PESO will review it.
                        </span>
                        <span x-show="state === 'failed'">The document could not be checked here. You can still submit it.</span>
                    </p>
                    @error("documents.{$key}")
                        <p id="doc-{{ $key }}-error" class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="exp-{{ $key }}" class="field-label">
                        Expiry date <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <input type="date" id="exp-{{ $key }}" name="expires_at[{{ $key }}]"
                           value="{{ old("expires_at.{$key}") }}"
                           class="field-input @error("expires_at.{$key}") field-input-error @enderror">
                    @error("expires_at.{$key}")<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        @endforeach
    </div>
</section>
