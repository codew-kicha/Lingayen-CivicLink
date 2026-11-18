{{--
    Step 6: the requirements other offices issue (or the organization writes), uploaded as scans or
    photos. Open only once the Desk Office has approved the application form (pre-final defense panel:
    nothing is paid or uploaded before that); the controller refuses uploads otherwise. Each upload is
    read in the browser for the pre-check (documentPrecheck) and saved straight away; a saved file
    shows with its verdict and can be replaced or removed. "Submit requirements" sends them back.
--}}
@php
    $saved = $draft->documents->keyBy('document_type');
@endphp

<x-application-frame :draft="$draft" :step="$step" :done="$done">
    @if ($draft->requirements_note)
        <div role="alert" class="mb-6 rounded-md border border-warning/40 bg-warning-subtle px-4 py-3 text-sm">
            <p class="font-semibold text-warning">The Desk Office returned your requirements</p>
            <p class="mt-1 text-ink">{{ $draft->requirements_note }}</p>
        </div>
    @else
        <div class="mb-6 flex items-start gap-3 rounded-md border border-success/40 bg-success-subtle px-4 py-3 text-sm">
            <x-phosphor-seal-check class="mt-hair h-5 w-5 shrink-0 text-success" aria-hidden="true" />
            <p class="text-ink">
                <span class="font-semibold text-success">Your application form was approved{{ $draft->form_approved_at ? ' on '.$draft->form_approved_at->format('d F Y') : '' }}.</span>
                Upload the requirements below, then submit them. Your application goes to the Sangguniang Bayan once the Desk Office has checked them.
            </p>
        </div>
    @endif

    {{-- The fee: none within a free period (or when the office waived it); else the amount and where to pay. --}}
    <p class="mb-6 flex items-start gap-3 rounded-md border border-line bg-paper px-4 py-3 text-sm text-ink">
        @if ($draft->fee_waived)
            <x-phosphor-seal-check class="mt-hair h-5 w-5 shrink-0 text-success" aria-hidden="true" />
            <span><span class="font-semibold">No accreditation fee</span> ({{ $draft->feeWaiverReason() }}), so no official receipt is needed.
                Once approved, your accreditation runs for {{ $draft->validityYears() }} years.</span>
        @else
            <x-phosphor-receipt class="mt-hair h-5 w-5 shrink-0 text-brand-600" aria-hidden="true" />
            <span><span class="font-semibold">Pay the {{ $draft->feeLabel() }} at the Municipal Treasury</span> and upload the official receipt below.
                Filed outside a free period, accreditation runs for one year and is renewed yearly.</span>
        @endif
    </p>

    <form method="POST" action="{{ route('cso.application.requirements') }}" enctype="multipart/form-data" class="card">
        @csrf
        <input type="hidden" name="step" value="requirements">

        <div class="border-b border-line px-6 py-4">
            <h2 class="card-title">Other requirements</h2>
            <p class="mt-1 max-w-[72ch] text-sm text-muted">
                PDF, JPG or PNG, up to 5 MB each. Each file is checked as you choose it; the Desk Office makes the final call.
                Add an expiry date if the document shows one.
            </p>
        </div>

        <div class="divide-y divide-line px-6">
            @foreach ($uploads as $key => $type)
                @php
                    $document = $saved->get($key);
                    $lowerLabel = preg_replace_callback('/\b[A-Za-z][a-z]+\b/', fn ($m) => strtolower($m[0]), $type['label']);
                @endphp
                <div class="grid gap-3 py-6 sm:grid-cols-[minmax(0,1fr)_11rem]">
                    <div x-data="documentPrecheck(@js($type['checks'] ?? []))" class="min-w-0">
                        <label for="doc-{{ $key }}" class="field-label">
                            {{ $type['label'] }}
                            @if ($type['optional'])<span class="font-normal text-muted">(optional)</span>@endif
                        </label>

                        @if ($document)
                            <div class="mb-3 flex flex-wrap items-center gap-3 rounded-sm border border-line bg-canvas px-3 py-2 text-sm">
                                <x-phosphor-paperclip class="h-4 w-4 shrink-0 text-muted" aria-hidden="true" />
                                <span class="min-w-0 flex-1 truncate font-semibold text-ink" title="{{ $document->original_filename }}">{{ $document->original_filename }}</span>
                                <x-ocr-badge :document="$document" compact />
                                <button type="submit" form="remove-{{ $key }}" class="btn-ghost btn-sm text-danger">Remove</button>
                            </div>
                            <p class="mb-1 text-xs text-muted">Choose a file below only to replace it.</p>
                        @endif

                        <x-file-field>
                            <input type="file" id="doc-{{ $key }}" name="documents[{{ $key }}]" accept=".pdf,.jpg,.jpeg,.png" @change="check($event)"
                                   aria-describedby="doc-{{ $key }}-hint doc-{{ $key }}-check @error("documents.{$key}") doc-{{ $key }}-error @enderror"
                                   class="field-file @error("documents.{$key}") field-input-error @enderror"
                                   @error("documents.{$key}") aria-invalid="true" @enderror>
                        </x-file-field>
                        <input type="hidden" name="ocr_text[{{ $key }}]" :value="text">
                        <p id="doc-{{ $key }}-hint" class="field-hint">{{ $key === 'fee_receipt' ? 'From the Municipal Treasury: the '.$draft->feeLabel().'.' : config("office.requirement_notes.{$key}") }}</p>

                        <p id="doc-{{ $key }}-check" aria-live="polite" x-cloak x-show="state !== 'idle'" class="mt-2 text-sm"
                           :class="{ 'text-muted': state === 'reading' || state === 'failed', 'text-success': state === 'matched', 'text-warning': state === 'mismatch' || state === 'unreadable' }">
                            <span x-show="state === 'reading'">Reading the document...</span>
                            <span x-show="state === 'matched'">This looks like the {{ $lowerLabel }}.</span>
                            <span x-show="state === 'mismatch'">This may not be the {{ $lowerLabel }}: we couldn't find <span x-text="missing.join('; ')"></span> in it. You can still save it; the Desk Office will review it.</span>
                            <span x-show="state === 'unreadable'">Very little text could be read (handwriting or a blurry photo). You can still save it.</span>
                            <span x-show="state === 'failed'">The document could not be checked here. You can still save it.</span>
                        </p>
                        @error("documents.{$key}")<p id="doc-{{ $key }}-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="exp-{{ $key }}" class="field-label">Expires <span class="font-normal text-muted">(optional)</span></label>
                        <input type="date" id="exp-{{ $key }}" name="expires_at[{{ $key }}]" value="{{ old("expires_at.{$key}", $document?->expires_at?->toDateString()) }}"
                               class="field-input @error("expires_at.{$key}") field-input-error @enderror">
                        @error("expires_at.{$key}")<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-line px-6 py-4">
            <a href="{{ route('cso.applications.show', $draft) }}" class="btn-ghost">
                <x-phosphor-arrow-left class="h-4 w-4" aria-hidden="true" />Your application
            </a>
            <button type="submit" name="action" value="later" class="btn-secondary ml-auto">Save and finish later</button>
            <button type="submit" name="action" value="submit" class="btn-primary">
                <x-phosphor-paper-plane-tilt class="h-4 w-4" aria-hidden="true" />Submit requirements
            </button>
        </div>
    </form>

    {{-- One small form per saved file, so Remove works from inside the main form. --}}
    @foreach ($saved as $key => $document)
        @if ($document->ocr_status !== 'generated')
            <form id="remove-{{ $key }}" method="POST" action="{{ route('cso.application.documents.destroy', $document) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach
</x-application-frame>
