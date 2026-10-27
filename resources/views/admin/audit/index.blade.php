<x-layouts.app header="Audit log"
                 subheader="Sign-ins, account changes, and decisions that grant or remove access. Entries cannot be edited.">
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="action" class="field-label">Event type</label>
            <select id="action" name="action" class="field-input">
                <option value="">All events</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>
                @endforeach
            </select>
        </div>
        <x-keep-query :only="['account', 'actor', 'organization']" />
        <button type="submit" class="btn-primary">Filter</button>
        @if (request()->hasAny(['action', 'account', 'actor', 'organization']))
            <a href="{{ route('admin.audit.index') }}" class="btn-ghost">Show everything</a>
        @endif
    </form>

    @if ($organization)
        <p class="mb-4 text-sm text-muted">
            Full history for <a href="{{ route('admin.organizations.edit', $organization) }}" class="entity-link">{{ $organization->name }}</a>.
        </p>
    @endif

    <section class="card">
        @if ($entries->isEmpty())
            <x-empty-state :filtered="request()->hasAny(['action', 'account', 'actor', 'organization'])" :clear-url="route('admin.audit.index')"
                           title="No events recorded yet." icon="list-magnifying-glass" />
        @else
            <div class="table-wrap table-sticky">
                <table class="w-full">
                    <caption class="sr-only">Audit log entries</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">When</th>
                            <th scope="col" class="table-head">Event</th>
                            <th scope="col" class="table-head">By</th>
                            <th scope="col" class="table-head">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr class="table-row">
                                <td class="table-date">
                                    {{ $entry->created_at->format('d M Y, H:i:s') }}
                                </td>
                                <td class="table-cell">
                                    <span class="font-mono text-xs">{{ $entry->action }}</span>
                                    @if ($entry->subject_type)
                                        <p class="text-xs text-muted">{{ $entry->subject_type }} #{{ $entry->subject_id }}</p>
                                    @endif
                                </td>
                                <td class="table-cell text-sm">{{ $entry->actor?->name ?? 'Not signed in' }}</td>
                                <td class="table-cell max-w-[40ch] text-xs text-muted">
                                    @foreach ($entry->meta ?? [] as $key => $value)
                                        <span class="block">{{ str_replace('_', ' ', $key) }}: {{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <x-pagination :paginator="$entries" />
    </section>
</x-layouts.app>
