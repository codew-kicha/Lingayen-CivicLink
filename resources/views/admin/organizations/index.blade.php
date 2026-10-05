<x-layouts.admin header="Organizations"
                 subheader="Every registered CSO. Hiding an organization removes it from the public directory without touching its record.">
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="q" class="field-label">Search</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}"
                   placeholder="Organization name" class="field-input w-72">
        </div>
        <label class="mb-2.5 flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="account" value="none" @checked(request('account') === 'none')
                   class="rounded-sm border-line-strong text-navy-600">
            No login yet
        </label>
        <label class="mb-2.5 flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="status" value="inactive" @checked(request('status') === 'inactive')
                   class="rounded-sm border-line-strong text-navy-600">
            Inactive only
        </label>
        <button type="submit" class="btn-primary">Search</button>
        @if (request()->hasAny(['q', 'account', 'status']))
            <a href="{{ route('admin.organizations.index') }}" class="btn-ghost">Clear</a>
        @endif
        <div class="ml-auto flex gap-2">
            <a href="{{ route('admin.organizations.import') }}" class="btn-ghost">Import list</a>
            <a href="{{ route('admin.organizations.create') }}" class="btn-secondary">Register organization</a>
        </div>
    </form>

    <section class="panel-flat overflow-hidden">
        @if ($organizations->isEmpty())
            <p class="px-4 py-14 text-center text-sm text-muted">No organizations are registered yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <caption class="sr-only">Registered organizations</caption>
                    <thead class="sticky top-0 bg-paper">
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Sector</th>
                            <th scope="col" class="table-head">Representative</th>
                            <th scope="col" class="table-head">Accreditation</th>
                            <th scope="col" class="table-head text-right">Verified activities</th>
                            <th scope="col" class="table-head">Public listing</th>
                            <th scope="col" class="table-head"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($organizations as $organization)
                            @php $accreditation = $organization->accreditations->first(); @endphp
                            <tr class="table-row">
                                <td class="table-cell">
                                    <a href="{{ route('admin.organizations.edit', $organization) }}"
                                       class="font-medium text-navy-700 hover:underline">{{ $organization->name }}</a>
                                    <p class="text-xs text-muted">Brgy. {{ $organization->barangay }}</p>
                                </td>
                                <td class="table-cell text-muted">{{ $organization->sector }}</td>
                                <td class="table-cell text-sm">
                                    @if (! $organization->user)
                                        <span class="badge-neutral">No login</span>
                                    @elseif (! $organization->user->is_active)
                                        <span class="badge-danger">Deactivated</span>
                                    @elseif (! $organization->user->email_verified_at)
                                        <span class="badge-warning">Invited</span>
                                    @else
                                        <span class="text-ink">{{ $organization->user->name }}</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @if ($accreditation)
                                        <x-status-badge status="active" />
                                        @if ($inactiveIds->contains($organization->id))
                                            <span class="badge-warning" title="No verified activity in {{ \App\Models\Organization::INACTIVE_AFTER_MONTHS }} months">Inactive</span>
                                        @endif
                                        <p class="mt-1 font-mono text-xs tabular-nums text-muted">
                                            to {{ $accreditation->expires_at->format('d M Y') }}
                                        </p>
                                    @else
                                        <span class="text-sm text-muted">Not accredited</span>
                                    @endif
                                </td>
                                <td class="table-num">{{ $organization->verified_activities_count }}</td>
                                <td class="table-cell">
                                    @if ($organization->public_visibility)
                                        <span class="badge-success">Listed</span>
                                    @else
                                        <span class="badge-neutral">Hidden</span>
                                    @endif
                                </td>
                                <td class="table-cell text-right">
                                    <form method="POST" action="{{ route('admin.organizations.visibility', $organization) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-ghost px-3 py-1.5">
                                            {{ $organization->public_visibility ? 'Hide' : 'List' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="mt-4">{{ $organizations->links() }}</div>
</x-layouts.admin>
