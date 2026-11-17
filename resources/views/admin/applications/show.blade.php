{{--
    One application under review. The left column is what PESO reads (status, documents with their
    pre-check, the organization and its officers); the right column holds the review actions and
    stays in view while scrolling. Cards share the header-row pattern and start at the top
    (items-start), so a short column never stretches.
--}}
@php
    $organization = $application->organization;
    $required = \App\Models\Document::requiredTypes();
    $uploadedRequired = $application->documents->whereIn('document_type', $required)->count();
    $flagged = $application->documents->whereIn('ocr_status', ['mismatch', 'unreadable'])->count();
    $positions = collect(preg_split('/\R/', (string) $organization->org_chart))->map(fn ($line) => trim($line))->filter();
@endphp

<x-layouts.app :header="$organization->name"
                 subheader="{{ Str::headline($application->type) }} application filed {{ $application->submitted_at?->format('d F Y') ?? 'not yet' }}">
    <x-slot:actions>
        <a href="{{ route('admin.applications.index') }}" class="btn-secondary">Back to queue</a>
    </x-slot:actions>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="card" aria-labelledby="status-title">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-6 py-4">
                    <h2 id="status-title" class="card-title">Review status</h2>
                    <span class="ml-auto"><x-status-badge :status="$application->status" /></span>
                </div>
                <div class="space-y-4 p-6">
                    <div>
                        <p class="text-sm font-semibold text-muted">Sangguniang Bayan reading stage</p>
                        <div class="mt-2"><x-sb-stepper :stage="$application->sb_stage" /></div>
                        <x-reading-history :readings="$application->readings" class="mt-4" />
                    </div>

                    <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted">Application form</dt>
                            <dd class="text-ink">
                                {{ $application->form_approved_at ? 'Approved '.$application->form_approved_at->format('d M Y') : 'Not approved yet' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted">Requirements</dt>
                            <dd class="text-ink">
                                @if ($application->requirements_submitted_at)
                                    Submitted {{ $application->requirements_submitted_at->format('d M Y') }}
                                @elseif ($application->status === 'requirements_pending')
                                    Waiting for the organization
                                @else
                                    Asked for after the form is approved
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($application->status === 'rejected' && $application->rejection_reason)
                        <div class="rounded-sm border border-danger bg-danger-subtle px-4 py-3">
                            <p class="text-sm font-semibold text-danger">Reason for rejection</p>
                            <p class="mt-1 text-sm text-danger">{{ $application->rejection_reason }}</p>
                        </div>
                    @endif

                    <dl class="grid gap-x-6 gap-y-2 border-t border-line pt-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-muted">Channel</dt>
                            <dd class="capitalize text-ink">{{ $application->submission_channel }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">Filed by</dt>
                            <dd class="text-ink">{{ $application->submittedBy?->name ?? 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ $application->reviewedBy ? 'Reviewed by' : 'Filed on' }}</dt>
                            <dd class="tabular-nums text-ink">
                                @if ($application->reviewedBy)
                                    {{ $application->reviewedBy->name }}, {{ $application->reviewed_at?->format('d M Y') }}
                                @else
                                    {{ $application->submitted_at?->format('d M Y') ?? '--' }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="card" aria-labelledby="documents-title">
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-line px-6 py-4">
                    <h2 id="documents-title" class="card-title">Submitted documents</h2>
                    <p class="text-sm text-muted">{{ $uploadedRequired }} of {{ count($required) }} required uploaded</p>
                    @if ($flagged)
                        <span class="badge-warning ml-auto">{{ $flagged }} to check</span>
                    @endif
                </div>

                @if ($application->documents->isEmpty())
                    <p class="px-6 py-12 text-center text-sm text-muted">No documents uploaded yet.</p>
                @else
                    <div class="table-wrap">
                        <table class="table-stack w-full">
                            <caption class="sr-only">Documents submitted with this application. Hover a "Looks right" badge for the words found.</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="table-head pl-6">Requirement</th>
                                    <th scope="col" class="table-head w-32">Expires</th>
                                    <th scope="col" class="table-head pr-6">Pre-check</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($application->documents as $document)
                                    <tr class="table-row">
                                        <td class="table-cell pl-6">
                                            <span class="block font-semibold text-ink">{{ \App\Models\Document::label($document->document_type) }}</span>
                                            <a href="{{ route('documents.download', $document) }}" target="_blank" rel="noopener"
                                               class="mt-px inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                                <x-phosphor-file-pdf class="h-4 w-4 shrink-0" aria-hidden="true" />{{ $document->original_filename }}
                                            </a>
                                        </td>
                                        <td class="table-date" data-label="Expires">{{ $document->expires_at?->format('d M Y') ?? '--' }}</td>
                                        <td class="table-cell pr-6"><x-ocr-badge :document="$document" compact /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card" aria-labelledby="organization-title">
                <div class="border-b border-line px-6 py-4">
                    <h2 id="organization-title" class="card-title">Organization</h2>
                </div>
                <dl class="grid gap-x-6 gap-y-4 p-6 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-muted">Sector</dt>
                        <dd class="text-ink">{{ $organization->sector }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Barangay</dt>
                        <dd class="text-ink">{{ $organization->barangay }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Positions</dt>
                        <dd class="text-ink">{{ $positions->isNotEmpty() ? $positions->join(', ') : 'Not provided' }}</dd>
                    </div>
                    <div class="sm:col-span-3">
                        <dt class="text-muted">Advocacy</dt>
                        <dd class="max-w-[72ch] text-ink">{{ $organization->advocacy ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card" aria-labelledby="members-title">
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-line px-6 py-4">
                    <h2 id="members-title" class="card-title">Officers and members</h2>
                    <p class="text-sm text-muted">Visible to PESO administrators only.</p>
                </div>
                @if ($organization->members->isEmpty())
                    <p class="p-6 text-sm text-muted">No members recorded.</p>
                @else
                    <div class="table-wrap">
                        <table class="table-stack w-full">
                            <caption class="sr-only">Officers and members on file</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="table-head pl-6">Name</th>
                                    <th scope="col" class="table-head">Position</th>
                                    <th scope="col" class="table-head pr-6">Contact number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($organization->members as $member)
                                    <tr>
                                        <td class="table-cell pl-6 font-semibold text-ink">{{ $member->name }}</td>
                                        <td class="table-cell text-muted" data-label="Position">{{ $member->position }}</td>
                                        <td class="table-cell pr-6 tabular-nums text-muted" data-label="Contact">{{ $member->contact_number ?: '--' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="xl:sticky xl:top-8">
            @can('review', $application)
                {{--
                    Pre-final defense panel's order: the form is approved first, then the requirements
                    come in, then the SB readings and the accreditation.
                --}}
                @if ($application->status === 'submitted')
                    <section class="card" aria-labelledby="review-title">
                        <div class="border-b border-line px-6 py-4">
                            <h2 id="review-title" class="card-title">Review the application form</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-muted">
                                Check the accreditation form and the member list. Approving asks the organization to upload the official
                                receipt and the other requirements; they are notified by email and on their dashboard.
                            </p>
                            <form method="POST" action="{{ route('admin.applications.approve-form', $application) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="btn-primary w-full">
                                    <x-phosphor-check class="h-4 w-4" aria-hidden="true" />Approve application form
                                </button>
                            </form>
                            @include('admin.applications.reject-form')
                        </div>
                    </section>
                @elseif ($application->status === 'requirements_pending')
                    <section class="card" aria-labelledby="review-title">
                        <div class="border-b border-line px-6 py-4">
                            <h2 id="review-title" class="card-title">Waiting for the requirements</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-muted">
                                Form approved {{ $application->form_approved_at?->format('d M Y') }}{{ $application->formApprovedBy ? ' by '.$application->formApprovedBy->name : '' }}.
                                The organization has been asked to upload:
                            </p>
                            <ul class="mt-2 list-disc pl-6 text-sm text-ink">
                                @foreach (\App\Services\ApplicationSteps::uploads($application) as $type)
                                    <li>{{ $type['label'] }}@if ($type['optional']) <span class="text-muted">(optional)</span>@endif</li>
                                @endforeach
                            </ul>
                            @if ($application->requirements_note)
                                <p class="mt-3 rounded-sm border border-warning/40 bg-warning-subtle px-3 py-2 text-sm text-ink">
                                    <span class="font-semibold text-warning">Returned with:</span> {{ $application->requirements_note }}
                                </p>
                            @endif
                            @include('admin.applications.reject-form')
                        </div>
                    </section>
                @elseif (in_array($application->status, ['requirements_submitted', 'under_review'], true))
                    <section class="card" aria-labelledby="review-title">
                        <div class="border-b border-line px-6 py-4">
                            <h2 id="review-title" class="card-title">Advance the review</h2>
                        </div>
                        <div class="p-6">
                            @php
                                [$previousStage, $nextStage] = $application->neighbouringStages();
                                $stages = \App\Models\ApplicationModel::SB_STAGES;
                            @endphp
                            <p class="field-label">Sangguniang Bayan stage</p>
                            <p class="text-sm text-ink">Now at <strong>{{ $stages[$application->sb_stage] ?? $application->sb_stage }}</strong>.</p>

                            {{--
                                Each reading is recorded with the session date, its outcome and remarks; the
                                organization receives them by email and in their notifications (pre-final
                                defense panel). Deferred or returned keeps the stage where it is.
                            --}}
                            @if ($nextStage)
                                @php $endorsement = $nextStage === 'endorsed'; @endphp
                                <form method="POST" action="{{ route('admin.applications.stage', $application) }}" class="mt-4 space-y-4 rounded-md border border-line bg-canvas p-4">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="sb_stage" value="{{ $nextStage }}">
                                    <p class="text-sm font-semibold text-ink">Record the {{ $endorsement ? 'endorsement' : strtolower($stages[$nextStage]) }}</p>

                                    <div>
                                        <label for="held_on" class="field-label">Date of the SB session</label>
                                        <input type="date" id="held_on" name="held_on" required max="{{ now()->toDateString() }}"
                                               value="{{ old('held_on', now()->toDateString()) }}"
                                               @error('held_on') aria-invalid="true" aria-describedby="held_on-error" @enderror
                                               class="field-input @error('held_on') field-input-error @enderror">
                                        @error('held_on')<p id="held_on-error" class="field-error">{{ $message }}</p>@enderror
                                    </div>

                                    @unless ($endorsement)
                                        <fieldset>
                                            <legend class="field-label">Outcome</legend>
                                            <div class="space-y-2">
                                                @foreach (\App\Models\ApplicationReading::OUTCOMES as $value => [$label, $meaning])
                                                    <label class="flex items-start gap-2 text-sm">
                                                        <input type="radio" name="outcome" value="{{ $value }}" @checked(old('outcome', 'passed') === $value) required
                                                               class="mt-hair h-4 w-4 shrink-0 border-line-strong text-primary">
                                                        <span><span class="font-semibold text-ink">{{ $label }}</span> <span class="text-muted">{{ $meaning }}</span></span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('outcome')<p class="field-error">{{ $message }}</p>@enderror
                                        </fieldset>
                                    @endunless

                                    <div>
                                        <label for="remarks" class="field-label">
                                            Remarks for the organization @if ($endorsement)<span class="font-normal text-muted">(optional)</span>@endif
                                        </label>
                                        <textarea id="remarks" name="remarks" rows="3" maxlength="2000" @required(! $endorsement)
                                                  placeholder="What the SB discussed, questions raised, and anything the organization should do"
                                                  @error('remarks') aria-invalid="true" aria-describedby="remarks-error" @enderror
                                                  class="field-input @error('remarks') field-input-error @enderror">{{ old('remarks') }}</textarea>
                                        <p class="field-hint">Sent to the organization by email and in their notifications.</p>
                                        @error('remarks')<p id="remarks-error" class="field-error">{{ $message }}</p>@enderror
                                    </div>

                                    <button type="submit" class="btn-secondary w-full">
                                        <x-phosphor-paper-plane-tilt class="h-4 w-4" aria-hidden="true" />
                                        Record {{ $endorsement ? 'endorsement' : strtolower($stages[$nextStage]) }} and notify
                                    </button>
                                </form>
                            @endif
                            @if ($previousStage)
                                <form method="POST" action="{{ route('admin.applications.stage', $application) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="sb_stage" value="{{ $previousStage }}">
                                    <button type="submit" class="btn-ghost btn-sm">Undo: back to {{ strtolower($stages[$previousStage]) }}</button>
                                    <p class="field-hint">For a mistake. Removes the last record; the organization is not notified.</p>
                                </form>
                            @endif
                            @error('sb_stage')<p class="field-error">{{ $message }}</p>@enderror

                            @php $endorsed = $application->sb_stage === 'endorsed'; @endphp
                            <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="mt-6 border-t border-line pt-6">
                                @csrf
                                <label for="expires_at" class="field-label">Accreditation valid until</label>
                                <input type="date" id="expires_at" name="expires_at" required
                                       value="{{ old('expires_at', now()->addYears(3)->toDateString()) }}"
                                       min="{{ now()->addDay()->toDateString() }}" max="{{ now()->addYears(10)->toDateString() }}"
                                       @disabled(! $endorsed)
                                       class="field-input mb-3 @error('expires_at') field-input-error @enderror">
                                @error('expires_at')<p class="field-error mb-3">{{ $message }}</p>@enderror
                                <button type="submit" class="btn-primary w-full" @disabled(! $endorsed)>Approve application</button>
                                <p class="field-hint">
                                    @if ($endorsed)
                                        Issues the accreditation until the date above (three years by default), with its reference code, and lists the organization in the public directory.
                                    @else
                                        Opens once the Sangguniang Bayan has endorsed the application (after the third reading).
                                    @endif
                                </p>
                            </form>

                            @if ($application->status === 'requirements_submitted')
                                {{-- Before the readings begin, the requirements can go back for a fix. --}}
                                <form method="POST" action="{{ route('admin.applications.return-requirements', $application) }}" class="mt-6 border-t border-line pt-6">
                                    @csrf
                                    <label for="requirements_note" class="field-label">Return the requirements</label>
                                    <textarea id="requirements_note" name="requirements_note" rows="2"
                                              class="field-input @error('requirements_note') field-input-error @enderror"
                                              @error('requirements_note') aria-invalid="true" aria-describedby="requirements_note-error" @enderror
                                              placeholder="e.g. The receipt is unreadable; upload a clearer scan">{{ old('requirements_note') }}</textarea>
                                    @error('requirements_note')<p id="requirements_note-error" class="field-error">{{ $message }}</p>@enderror
                                    <button type="submit" class="btn-secondary mt-3 w-full">Send back for correction</button>
                                </form>
                            @endif

                            @include('admin.applications.reject-form')
                        </div>
                    </section>
                @else
                    <section class="card p-6">
                        <h2 class="card-title">Review closed</h2>
                        <p class="mt-2 text-sm text-muted">
                            This application is {{ Str::lower(Str::headline($application->status)) }} and no longer accepts review actions.
                        </p>
                    </section>
                @endif
            @endcan
        </aside>
    </div>
</x-layouts.app>
