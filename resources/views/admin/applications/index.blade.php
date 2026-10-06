<x-layouts.app header="Applications" subheader="Accreditation applications filed by CSOs, newest first.">
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-input w-48">
                <option value="">All statuses</option>
                @foreach (['submitted', 'under_review', 'approved', 'rejected', 'draft'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="stage" class="field-label">SB stage</label>
            <select id="stage" name="stage" class="field-input w-48">
                <option value="">All stages</option>
                @foreach (['not_endorsed', 'first_reading', 'second_reading', 'third_reading', 'endorsed'] as $stage)
                    <option value="{{ $stage }}" @selected(request('stage') === $stage)>
                        {{ Str::headline($stage) }}
                    </option>
                @endforeach
            </select>
        </div>
        <x-keep-query />
        <button type="submit" class="btn-primary">Filter</button>
        @if (request()->hasAny(['status', 'stage']))
            <a href="{{ route('admin.applications.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    <section class="card">
        @if ($applications->isEmpty())
            <x-empty-state :filtered="request()->hasAny(['status', 'stage'])" :clear-url="route('admin.applications.index')"
                           title="No applications have been filed yet." icon="clipboard-text" />
        @else
            <div class="table-wrap table-sticky">
                <table class="w-full">
                    <caption class="sr-only">Accreditation applications</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Channel</th>
                            <th scope="col" class="table-head">Status</th>
                            <th scope="col" class="table-head">SB stage</th>
                            <th scope="col" class="table-head">Filed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <a href="{{ route('admin.applications.show', $application) }}"
                                       class="entity-link">
                                        {{ $application->organization->name }}
                                    </a>
                                    <x-application-type :type="$application->type" />
                                    <p class="text-xs text-muted">Brgy. {{ $application->organization->barangay }}</p>
                                    @if ($application->flagged_documents_count)
                                        <span class="badge-warning mt-1">{{ $application->flagged_documents_count }} {{ Str::plural('document', $application->flagged_documents_count) }} to check</span>
                                    @endif
                                </td>
                                <td class="table-cell capitalize text-muted">{{ $application->submission_channel }}</td>
                                <td class="table-cell"><x-status-badge :status="$application->status" /></td>
                                <td class="table-cell text-muted">{{ Str::headline($application->sb_stage) }}</td>
                                <td class="table-date">
                                    {{ $application->submitted_at?->format('d M Y') ?? '--' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <x-pagination :paginator="$applications" :default="25" />
</x-layouts.app>
