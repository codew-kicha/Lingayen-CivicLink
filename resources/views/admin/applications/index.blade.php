<x-layouts.app header="Applications" subheader="Accreditation applications filed by CSOs. Waiting applications come first, then the newest.">
    <x-slot:actions>
        {{-- Same chip, search and order as the list below. --}}
        <a href="{{ route('admin.applications.export', request()->only(['status', 'q', 'direction'])) }}" download class="btn-secondary">
            <x-phosphor-microsoft-excel-logo class="h-4 w-4" aria-hidden="true" />Download Excel
        </a>
        <a href="{{ route('admin.organizations.create') }}" class="btn-primary"
           @click.prevent="$dispatch('open-dialog', 'register-organization')">
            <x-phosphor-plus class="h-4 w-4" aria-hidden="true" />Register an organization
        </a>
    </x-slot:actions>

    <x-filter-bar search="Search organization or barangay"
                  :chips="['' => 'All', 'review' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected']" />

    @php $filtered = request()->filled('status') || request()->filled('q'); @endphp

    <section class="card">
        @if ($applications->isEmpty())
            <x-empty-state :filtered="$filtered" :clear-url="route('admin.applications.index')"
                           title="No applications have been filed yet." icon="clipboard-text" />
        @else
            <div class="table-scroll">
                <table class="w-full">
                    <caption class="sr-only">Accreditation applications. Use the arrow keys to move between rows and Enter to open one.</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Status</th>
                            <th scope="col" class="table-head table-head-sorted w-36" aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}">
                                <a href="{{ request()->fullUrlWithQuery(['direction' => $direction === 'asc' ? null : 'asc', 'page' => null]) }}"
                                   class="inline-flex items-center gap-1 rounded-sm hover:text-primary">
                                    Filed
                                    <x-dynamic-component :component="$direction === 'asc' ? 'phosphor-arrow-up' : 'phosphor-arrow-down'" class="h-4 w-4" aria-hidden="true" />
                                    <span class="sr-only">{{ $direction === 'asc' ? ', oldest first. Sort newest first' : ', newest first. Sort oldest first' }}</span>
                                </a>
                            </th>
                            <th scope="col" class="table-head w-12"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody x-data="tableNav" @click="onClick" @keydown="onKeydown">
                        @foreach ($applications as $application)
                            <tr class="table-row-link" data-row data-href="{{ route('admin.applications.show', $application) }}"
                                tabindex="{{ $loop->first ? 0 : -1 }}">
                                <td class="table-cell min-w-64">
                                    <a href="{{ route('admin.applications.show', $application) }}" tabindex="-1" class="font-semibold text-ink">
                                        {{ $application->organization->name }}
                                    </a>
                                    <x-application-type :type="$application->type" />
                                    <p class="text-sm text-muted">Brgy. {{ $application->organization->barangay }}</p>
                                    @if ($application->flagged_documents_count)
                                        <span class="badge-warning mt-1">{{ $application->flagged_documents_count }} {{ Str::plural('document', $application->flagged_documents_count) }} to check</span>
                                    @endif
                                </td>
                                <td class="table-cell whitespace-nowrap">
                                    <x-status-badge :status="$application->status" />
                                    <p class="mt-1 text-sm text-muted">SB: {{ $application->sb_stage === 'not_endorsed' ? 'Not endorsed yet' : Str::headline($application->sb_stage) }}</p>
                                </td>
                                <td class="table-date">{{ $application->submitted_at?->format('d M Y') ?? 'Not filed' }}</td>
                                <td class="table-cell text-right text-muted">
                                    <x-phosphor-caret-right class="ml-auto h-5 w-5" aria-hidden="true" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-pagination :paginator="$applications" />
    </section>

    @include('admin.organizations.register-dialog')
</x-layouts.app>
