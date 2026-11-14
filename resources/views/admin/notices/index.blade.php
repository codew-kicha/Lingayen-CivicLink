<x-layouts.app header="Notices" subheader="Messages from the Desk Office to organizations. Each one appears under the representative's Notifications.">
    <x-slot:actions>
        <button type="button" class="btn-primary" @click="$dispatch('open-dialog', 'send-notice')">
            <x-phosphor-megaphone class="h-4 w-4" aria-hidden="true" />Send a notice
        </button>
    </x-slot:actions>

    <section class="card">
        @if ($notices->isEmpty())
            <x-empty-state title="No notices sent yet." icon="megaphone">
                Send meeting announcements, deadlines or reminders to one organization, a sector, a barangay, or everyone.
            </x-empty-state>
        @else
            <div class="table-wrap">
                <table class="table-stack w-full">
                    <caption class="sr-only">Notices sent</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head pl-6">Notice</th>
                            <th scope="col" class="table-head">Sent to</th>
                            <th scope="col" class="table-head">Reached</th>
                            <th scope="col" class="table-head w-36 pr-6">Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notices as $notice)
                            <tr class="table-row">
                                <td class="table-cell pl-6">
                                    <p class="font-semibold text-ink">{{ $notice->title }}</p>
                                    <p class="line-clamp-2 max-w-[60ch] text-sm text-muted">{{ $notice->body }}</p>
                                </td>
                                <td class="table-cell text-sm" data-label="Sent to">{{ $notice->audienceLabel() }}</td>
                                <td class="table-cell text-sm tabular-nums" data-label="Reached">
                                    {{ $notice->recipients }} {{ Str::plural('organization', $notice->recipients) }}
                                    @if ($notice->emailed)<span class="badge-info ml-1">Emailed</span>@endif
                                </td>
                                <td class="table-date pr-6" data-label="Sent">
                                    {{ $notice->created_at->format('d M Y') }}
                                    <span class="block text-xs text-muted">{{ $notice->sender?->name }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$notices" />
        @endif
    </section>

    @include('admin.notices.compose-dialog')
</x-layouts.app>
