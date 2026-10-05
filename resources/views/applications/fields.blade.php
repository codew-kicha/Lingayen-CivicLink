{{-- Application type and requirement uploads, shared by the CSO form and PESO assisted encoding. --}}
<section class="panel p-5">
    <h2 class="font-semibold text-ink">Application type</h2>

    <fieldset class="mt-4">
        <legend class="sr-only">Application type</legend>
        <div class="space-y-2">
            @foreach ([
                'new' => 'New accreditation, this organization has not been accredited before',
                'renewal' => 'Renewal of an existing accreditation',
            ] as $value => $label)
                <label class="flex items-start gap-3 rounded-sm border border-line-strong p-3
                              hover:border-navy-300 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50">
                    <input type="radio" name="type" value="{{ $value }}" class="mt-0.5"
                           @checked(old('type', $hasActiveAccreditation ? 'renewal' : 'new') === $value) required>
                    <span class="text-sm text-ink">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('type')<p class="field-error">{{ $message }}</p>@enderror
    </fieldset>
</section>

<section class="panel p-5">
    <h2 class="font-semibold text-ink">Requirements</h2>
    <p class="mt-0.5 text-sm text-muted">
        Upload each required document. Add an expiry date where the document carries one, so
        we can remind you before it lapses.
    </p>

    <div class="mt-5 space-y-5">
        @foreach ($documentTypes as $key => $type)
            <div class="grid gap-3 border-t border-line pt-5 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <div>
                    <label for="doc-{{ $key }}" class="field-label">
                        {{ $type['label'] }}
                        @if ($type['optional'])
                            <span class="font-normal text-muted">(optional, only if you have one)</span>
                        @endif
                    </label>
                    <input type="file" id="doc-{{ $key }}" name="documents[{{ $key }}]"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="field-input @error("documents.{$key}") field-input-error @enderror"
                           @error("documents.{$key}") aria-invalid="true" aria-describedby="doc-{{ $key }}-error" @enderror
                           @required(! $type['optional'])>
                    <p class="field-hint">{{ config("office.requirement_notes.{$key}") }}</p>
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
