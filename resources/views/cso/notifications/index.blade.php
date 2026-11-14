<x-layouts.app header="Notifications" subheader="Notices from the Civil Society Desk Office and updates on your applications and activities.">
    <section class="card">
        @if ($notifications->isEmpty())
            <x-empty-state title="Nothing here yet." icon="bell">
                Notices from the Desk Office and updates on your application appear here.
            </x-empty-state>
        @else
            <ul class="divide-y divide-line">
                @foreach ($notifications as $notification)
                    @php $isNotice = ($notification->data['kind'] ?? null) === 'notice'; @endphp
                    <li class="flex gap-4 px-6 py-4">
                        <span @class([
                            'mt-hair flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                            'bg-accent-100 text-accent-700' => $isNotice,
                            'bg-canvas text-muted' => ! $isNotice,
                        ]) aria-hidden="true">
                            <x-dynamic-component :component="$isNotice ? 'phosphor-megaphone' : 'phosphor-bell'" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-semibold text-ink">
                                {{ $notification->data['headline'] ?? 'Update' }}
                                @unless ($notification->read_at)<span class="badge-info">New</span>@endunless
                                <span class="ml-auto text-xs font-normal text-muted" title="{{ $notification->created_at->format('d M Y, g:i A') }}">{{ $notification->created_at->diffForHumans() }}</span>
                            </p>
                            @if ($isNotice)
                                <p class="text-xs text-muted">From the {{ $notification->data['from'] ?? 'Civil Society Desk Office' }}</p>
                            @endif
                            <p class="mt-1 whitespace-pre-line text-sm text-muted">{{ $notification->data['body'] ?? '' }}</p>
                            @if (! empty($notification->data['url']))
                                <a href="{{ $notification->data['url'] }}" class="mt-2 inline-flex text-sm font-semibold text-primary hover:underline">Open</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-pagination :paginator="$notifications" />
        @endif
    </section>
</x-layouts.app>
