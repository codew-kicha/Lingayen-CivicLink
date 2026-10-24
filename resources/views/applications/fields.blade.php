{{-- Application type and requirement uploads, shared by the CSO form and the admin "File an application"
     dialog. No card of its own, so it sits in either. Documents marked new_only (constitution and
     by-laws) appear only for a new application. --}}
<div x-data="{ type: @js(old('type', $hasActiveAccreditation ? 'renewal' : 'new')) }">
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
                           @checked(old('type', $hasActiveAccreditation ? 'renewal' : 'new') === $value) required>
                    <span>
                        <span class="block text-sm font-semibold text-ink">{{ $title }}</span>
                        <span class="block text-sm text-muted">{{ $description }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('type')<p class="field-error">{{ $message }}</p>@enderror
    </fieldset>

    <h3 class="mt-6 text-sm font-semibold text-ink">Requirements</h3>
    <p class="mt-1 text-sm text-muted">PDF, JPG or PNG, up to 5 MB each. Add an expiry date if the document shows one.</p>

    <div class="mt-2 divide-y divide-line border-t border-line">
        @foreach ($documentTypes as $key => $type)
            <div class="grid gap-3 py-4 sm:grid-cols-[minmax(0,1fr)_11rem]"
                 @if (! empty($type['new_only'])) x-show="type === 'new'" @endif>
                <div x-data="documentPrecheck(@js($type['keywords'] ?? []), {{ $type['min_matches'] ?? 1 }})" class="min-w-0">
                    <label for="doc-{{ $key }}" class="field-label">
                        {{ $type['label'] }}
                        @if ($type['optional'])
                            <span class="font-normal text-muted">(optional)</span>
                        @endif
                    </label>
                    <input type="file" id="doc-{{ $key }}" name="documents[{{ $key }}]"
                           accept=".pdf,.jpg,.jpeg,.png" @change="check($event)"
                           aria-describedby="doc-{{ $key }}-hint doc-{{ $key }}-check @error("documents.{$key}") doc-{{ $key }}-error @enderror"
                           class="field-file @error("documents.{$key}") field-input-error @enderror"
                           @error("documents.{$key}") aria-invalid="true" @enderror
                           @if (! empty($type['new_only'])) :disabled="type !== 'new'" @endif
                           @required(! $type['optional'])>
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
