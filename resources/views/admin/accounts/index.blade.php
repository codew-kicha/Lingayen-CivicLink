<x-layouts.admin header="Accounts"
                 subheader="Everyone who can sign in: PESO administrators and CSO representatives.">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="q" class="field-label">Search</label>
                <input type="search" id="q" name="q" value="{{ request('q') }}"
                       placeholder="Name, email, or organization" class="field-input w-72">
            </div>
            <div>
                <label for="role" class="field-label">Role</label>
                <select id="role" name="role" class="field-input">
                    <option value="">All roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>PESO admin</option>
                    <option value="cso_rep" @selected(request('role') === 'cso_rep')>CSO representative</option>
                </select>
            </div>
            <div>
                <label for="status" class="field-label">Status</label>
                <select id="status" name="status" class="field-input">
                    <option value="">Any status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="pending" @selected(request('status') === 'pending')>Invitation pending</option>
                    <option value="deactivated" @selected(request('status') === 'deactivated')>Deactivated</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
            @if (request()->hasAny(['q', 'role', 'status']))
                <a href="{{ route('admin.accounts.index') }}" class="btn-ghost">Clear</a>
            @endif
        </form>

        <div class="ml-auto flex gap-2">
            <a href="{{ route('admin.organizations.create') }}" class="btn-secondary">Register organization</a>
            <a href="{{ route('admin.accounts.create') }}" class="btn-primary">Add PESO admin</a>
        </div>
    </div>

    <section class="panel-flat overflow-hidden">
        @if ($accounts->isEmpty())
            <p class="px-4 py-14 text-center text-sm text-muted">No accounts match those filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <caption class="sr-only">Accounts</caption>
                    <thead class="sticky top-0 bg-paper">
                        <tr>
                            <th scope="col" class="table-head">Name</th>
                            <th scope="col" class="table-head">Role</th>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Status</th>
                            <th scope="col" class="table-head">Last sign-in</th>
                            <th scope="col" class="table-head"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <p class="font-medium text-ink">{{ $account->name }}</p>
                                    <p class="text-xs text-muted">{{ $account->email }}</p>
                                </td>
                                <td class="table-cell text-muted">{{ $account->isAdmin() ? 'PESO admin' : 'CSO representative' }}</td>
                                <td class="table-cell">
                                    @if ($account->organization)
                                        <a href="{{ route('admin.organizations.edit', $account->organization) }}"
                                           class="text-navy-700 hover:underline">{{ $account->organization->name }}</a>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @if (! $account->is_active)
                                        <span class="badge-danger">Deactivated</span>
                                        <p class="mt-1 max-w-[28ch] text-xs text-muted">{{ $account->deactivation_reason }}</p>
                                    @elseif (! $account->email_verified_at)
                                        <span class="badge-warning">Invitation pending</span>
                                    @else
                                        <span class="badge-success">Active</span>
                                    @endif
                                </td>
                                <td class="table-cell font-mono text-xs tabular-nums text-muted">
                                    {{ $account->last_login_at?->format('d M Y, H:i') ?? 'Never' }}
                                </td>
                                <td class="table-cell">
                                    <div class="flex flex-wrap justify-end gap-1">
                                        <a href="{{ route('admin.audit.index', ['account' => $account->id]) }}" class="btn-ghost px-3 py-1.5">History</a>

                                        @can('invite', $account)
                                            <form method="POST" action="{{ route('admin.accounts.invitation', $account) }}">
                                                @csrf
                                                <button type="submit" class="btn-ghost px-3 py-1.5">Resend invitation</button>
                                            </form>
                                        @endcan

                                        @can('reactivate', $account)
                                            <form method="POST" action="{{ route('admin.accounts.reactivate', $account) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn-ghost px-3 py-1.5">Reactivate</button>
                                            </form>
                                        @endcan

                                        @can('deactivate', $account)
                                            <details class="relative">
                                                <summary class="btn-ghost cursor-pointer list-none px-3 py-1.5 text-danger-600">Deactivate</summary>
                                                <form method="POST" action="{{ route('admin.accounts.deactivate', $account) }}"
                                                      class="absolute right-0 z-dropdown mt-1 w-72 space-y-2 rounded-lg border border-line bg-paper p-3 shadow-overlay">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label for="reason-{{ $account->id }}" class="field-label">Reason (kept in the audit log)</label>
                                                    <textarea id="reason-{{ $account->id }}" name="reason" rows="2" required minlength="10"
                                                              class="field-input"></textarea>
                                                    <button type="submit" class="btn-danger w-full">Deactivate {{ $account->name }}</button>
                                                </form>
                                            </details>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @error('reason')
        <p class="field-error mt-3">{{ $message }}</p>
    @enderror

    <div class="mt-4">{{ $accounts->links() }}</div>
</x-layouts.admin>
