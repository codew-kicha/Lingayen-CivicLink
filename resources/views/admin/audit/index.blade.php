<x-layouts.admin header="Audit log"
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
        @if (request('account'))
            <input type="hidden" name="account" value="{{ request('account') }}">
        @endif
        <button type="submit" class="btn-primary">Filter</button>
        @if (request()->hasAny(['action', 'account', 'actor']))
            <a href="{{ route('admin.audit.index') }}" class="btn-ghost">Show everything</a>
        @endif
    </form>

    <section class="panel-flat overflow-hidden">
        @if ($entries->isEmpty())
            <p class="px-4 py-14 text-center text-sm text-muted">No events recorded for this filter.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <caption class="sr-only">Audit log entries</caption>
                    <thead class="sticky top-0 bg-paper">
                        <tr>
                            <th scope="col" class="table-head">When</th>
                            <th scope="col" class="table-head">Event</th>
                            <th scope="col" class="table-head">By</th>
                            <th scope="col" class="table-head">Details</th>
                            <th scope="col" class="table-head">IP address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr class="table-row">
                                <td class="table-cell whitespace-nowrap font-mono text-xs tabular-nums text-muted">
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
                                <td class="table-cell font-mono text-xs tabular-nums text-muted">{{ $entry->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="mt-4">{{ $entries->links() }}</div>
</x-layouts.admin>
