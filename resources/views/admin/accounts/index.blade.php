<x-layouts.app header="Accounts"
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
            <a href="{{ route('admin.organizations.create') }}" class="btn-secondary"
               @click.prevent="$dispatch('open-dialog', 'register-organization')">Register an organization</a>
            @if (config('app.admin_invites'))
                <a href="{{ route('admin.accounts.create') }}" class="btn-primary">Add PESO admin</a>
            @endif
        </div>
    </div>

    <section class="card">
        @if ($accounts->isEmpty())
            <x-empty-state :filtered="request()->hasAny(['q', 'role', 'status'])" :clear-url="route('admin.accounts.index')"
                           title="No accounts yet." icon="users" />
        @else
            <div class="table-wrap table-sticky">
                <table class="w-full">
                    <caption class="sr-only">Accounts</caption>
                    <thead>
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
                                    <p class="font-semibold text-ink">{{ $account->name }}</p>
                                    <p class="text-xs text-muted">{{ $account->email }}</p>
                                </td>
                                <td class="table-cell text-muted">{{ $account->isAdmin() ? 'PESO admin' : 'CSO representative' }}</td>
                                <td class="table-cell">
                                    @if ($account->organization)
                                        <a href="{{ route('admin.organizations.edit', $account->organization) }}"
                                           class="entity-link">{{ $account->organization->name }}</a>
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
                                <td class="table-date">
                                    {{ $account->last_login_at?->format('d M Y, H:i') ?? 'Never' }}
                                </td>
                                <td class="table-cell">
                                    <div class="flex flex-wrap justify-end gap-1">
                                        <a href="{{ route('admin.audit.index', ['account' => $account->id]) }}" class="btn-ghost px-3 py-2">History</a>

                                        @can('manageCredentials', $account)
                                            <button type="button" class="btn-ghost px-3 py-2"
                                                    @click="$dispatch('edit-login', @js(['id' => $account->id, 'name' => $account->name, 'email' => $account->email, 'phone' => $account->phone, 'organization' => $account->organization?->name, 'url' => route('admin.accounts.update', $account)]))">
                                                <x-phosphor-key class="h-4 w-4" aria-hidden="true" />Edit login
                                            </button>
                                        @endcan

                                        @can('invite', $account)
                                            <form method="POST" action="{{ route('admin.accounts.invitation', $account) }}">
                                                @csrf
                                                <button type="submit" class="btn-ghost px-3 py-2">Resend invitation</button>
                                            </form>
                                        @endcan

                                        @can('reactivate', $account)
                                            <form method="POST" action="{{ route('admin.accounts.reactivate', $account) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn-ghost px-3 py-2">Reactivate</button>
                                            </form>
                                        @endcan

                                        @can('deactivate', $account)
                                            <details class="relative">
                                                <summary class="btn-ghost cursor-pointer list-none px-3 py-2 text-danger">Deactivate</summary>
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
        <x-pagination :paginator="$accounts" />
    </section>

    @error('reason')
        <p class="field-error mt-3">{{ $message }}</p>
    @enderror

    @include('admin.accounts.edit-login-dialog')
    @include('admin.organizations.register-dialog')
</x-layouts.app>
