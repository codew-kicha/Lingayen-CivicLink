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
                    </div>

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
                @if (in_array($application->status, ['submitted', 'under_review'], true))
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
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                @if ($nextStage)
                                    <form method="POST" action="{{ route('admin.applications.stage', $application) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="sb_stage" value="{{ $nextStage }}">
                                        <button type="submit" class="btn-secondary">
                                            Record: {{ $stages[$nextStage] }} <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </form>
                                @endif
                                @if ($previousStage)
                                    <form method="POST" action="{{ route('admin.applications.stage', $application) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="sb_stage" value="{{ $previousStage }}">
                                        <button type="submit" class="btn-ghost btn-sm">Back to {{ strtolower($stages[$previousStage]) }}</button>
                                    </form>
                                @endif
                            </div>
                            @error('sb_stage')<p class="field-error">{{ $message }}</p>@enderror

                            @php $endorsed = $application->sb_stage === 'endorsed'; @endphp
                            <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="mt-6 border-t border-line pt-6">
                                @csrf
                                <button type="submit" class="btn-primary w-full" @disabled(! $endorsed)>Approve application</button>
                                <p class="field-hint">
                                    @if ($endorsed)
                                        Issues an accreditation valid for three years, with its reference code, and lists the organization in the public directory.
                                    @else
                                        Opens once the Sangguniang Bayan has endorsed the application (after the third reading).
                                    @endif
                                </p>
                            </form>

                            <form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="mt-6 border-t border-line pt-6">
                                @csrf
                                <label for="rejection_reason" class="field-label">Reason for rejection</label>
                                <textarea id="rejection_reason" name="rejection_reason" rows="2"
                                          class="field-input @error('rejection_reason') field-input-error @enderror"
                                          @error('rejection_reason') aria-invalid="true" aria-describedby="rejection_reason-error" @enderror
                                          placeholder="What the organization needs to correct">{{ old('rejection_reason') }}</textarea>
                                @error('rejection_reason')
                                    <p id="rejection_reason-error" class="field-error">{{ $message }}</p>
                                @enderror
                                <button type="submit" class="btn-danger mt-3 w-full">Reject application</button>
                            </form>
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
