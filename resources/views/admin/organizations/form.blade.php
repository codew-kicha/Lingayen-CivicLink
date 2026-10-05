@php $editing = $organization->exists; @endphp

<x-layouts.admin :header="$editing ? $organization->name : 'Register an organization'"
                 :subheader="$editing ? 'Brgy. '.$organization->barangay.' · '.$organization->sector : 'For walk-in applicants and records the office already holds.'">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
        <form method="POST" action="{{ $editing ? route('admin.organizations.update', $organization) : route('admin.organizations.store') }}"
              class="panel space-y-5 p-6">
            @csrf
            @if ($editing) @method('PATCH') @endif

            <h2 class="font-semibold text-ink">Organization details</h2>

            <div>
                <label for="name" class="field-label">Organization name</label>
                <input id="name" name="name" value="{{ old('name', $organization->name) }}" required
                       class="field-input @error('name') field-input-error @enderror"
                       @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="barangay" class="field-label">Barangay</label>
                    <select id="barangay" name="barangay" required class="field-input @error('barangay') field-input-error @enderror">
                        <option value="">Choose a barangay</option>
                        @foreach ($barangays as $barangay)
                            <option @selected(old('barangay', $organization->barangay) === $barangay)>{{ $barangay }}</option>
                        @endforeach
                    </select>
                    @error('barangay')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sector" class="field-label">Sector</label>
                    <select id="sector" name="sector" required class="field-input @error('sector') field-input-error @enderror">
                        <option value="">Choose a sector</option>
                        @foreach ($sectors as $sector)
                            <option @selected(old('sector', $organization->sector) === $sector)>{{ $sector }}</option>
                        @endforeach
                    </select>
                    @error('sector')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="advocacy" class="field-label">Advocacy <span class="font-normal text-muted">(optional)</span></label>
                <textarea id="advocacy" name="advocacy" rows="3" class="field-input">{{ old('advocacy', $organization->advocacy) }}</textarea>
                @error('advocacy')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            @unless ($editing)
                <fieldset class="space-y-5 border-t border-line pt-5">
                    <legend class="font-semibold text-ink">Representative <span class="font-normal text-muted">(optional)</span></legend>
                    <p class="text-sm text-muted">
                        If you enter an email, an account is created and the representative receives a link to set
                        their own password. Leave blank to register the organization without a login.
                    </p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="rep_name" class="field-label">Full name</label>
                            <input id="rep_name" name="rep_name" value="{{ old('rep_name') }}" class="field-input @error('rep_name') field-input-error @enderror">
                            @error('rep_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="rep_email" class="field-label">Email</label>
                            <input id="rep_email" name="rep_email" type="email" value="{{ old('rep_email') }}" class="field-input @error('rep_email') field-input-error @enderror">
                            @error('rep_email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="sm:w-1/2">
                        <label for="rep_phone" class="field-label">Mobile number</label>
                        <input id="rep_phone" name="rep_phone" type="tel" value="{{ old('rep_phone') }}" placeholder="0917 123 4567"
                               class="field-input @error('rep_phone') field-input-error @enderror">
                        @error('rep_phone')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </fieldset>
            @endunless

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">{{ $editing ? 'Save details' : 'Register organization' }}</button>
                <a href="{{ route('admin.organizations.index') }}" class="btn-ghost">Back to organizations</a>
            </div>
        </form>

        @if ($editing)
            <div class="space-y-6">
                <section class="panel p-6">
                    <h2 class="font-semibold text-ink">Representative account</h2>
                    @if ($organization->user)
                        <p class="mt-3 font-medium text-ink">{{ $organization->user->name }}</p>
                        <p class="text-sm text-muted">{{ $organization->user->email }}{{ $organization->user->phone ? ' · '.$organization->user->phone : '' }}</p>
                        <p class="mt-2">
                            @if (! $organization->user->is_active)
                                <span class="badge-danger">Deactivated</span>
                            @elseif (! $organization->user->email_verified_at)
                                <span class="badge-warning">Invitation pending</span>
                            @else
                                <span class="badge-success">Active</span>
                            @endif
                        </p>
                        <a href="{{ route('admin.accounts.index', ['q' => $organization->user->email]) }}" class="btn-ghost mt-3 px-3">Manage account</a>
                    @else
                        <p class="mt-2 text-sm text-muted">No one can sign in for this organization yet. Invite its representative:</p>
                        <form method="POST" action="{{ route('admin.organizations.account', $organization) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label for="acct-name" class="field-label">Full name</label>
                                <input id="acct-name" name="name" value="{{ old('name') }}" required class="field-input">
                            </div>
                            <div>
                                <label for="acct-email" class="field-label">Email</label>
                                <input id="acct-email" name="email" type="email" value="{{ old('email') }}" required class="field-input">
                                @error('email')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="acct-phone" class="field-label">Mobile number <span class="font-normal text-muted">(optional)</span></label>
                                <input id="acct-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="0917 123 4567" class="field-input">
                                @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="btn-primary">Send invitation</button>
                        </form>
                    @endif
                </section>

                <section class="panel p-6">
                    <h2 class="font-semibold text-ink">Encode on their behalf</h2>
                    <p class="mt-1 text-sm text-muted">For paper submissions brought to the office.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.organizations.applications.create', $organization) }}" class="btn-secondary">File an application</a>
                        <a href="{{ route('admin.organizations.activities.create', $organization) }}" class="btn-secondary">Log an activity</a>
                    </div>
                </section>

                <section class="panel p-6">
                    <h2 class="font-semibold text-ink">Accreditation</h2>
                    @php $active = $organization->accreditations->firstWhere('status', 'active'); @endphp
                    @if ($active)
                        <p class="mt-3 flex items-center gap-2">
                            <x-status-badge status="active" />
                            <span class="font-mono text-sm tabular-nums text-muted">{{ $active->verification_code }}</span>
                        </p>
                        <p class="mt-1 text-sm text-muted">Valid until {{ $active->expires_at->format('d M Y') }}</p>
                        <details class="mt-4">
                            <summary class="btn-ghost cursor-pointer list-none px-3 text-danger-600">Revoke accreditation</summary>
                            <form method="POST" action="{{ route('admin.organizations.revoke', $organization) }}" class="mt-3 space-y-3">
                                @csrf
                                <label for="revoke-reason" class="field-label">Reason (kept in the audit log)</label>
                                <textarea id="revoke-reason" name="reason" rows="3" required minlength="10" class="field-input">{{ old('reason') }}</textarea>
                                @error('reason')<p class="field-error">{{ $message }}</p>@enderror
                                <button type="submit" class="btn-danger">Revoke {{ $active->verification_code }}</button>
                            </form>
                        </details>
                    @else
                        <p class="mt-2 text-sm text-muted">
                            Not currently accredited.
                            @if ($organization->accreditations->isNotEmpty())
                                Last record: {{ ucfirst($organization->accreditations->first()->status) }}.
                            @endif
                        </p>
                    @endif
                </section>

                <section class="panel p-6">
                    <h2 class="font-semibold text-ink">Recent history</h2>
                    @forelse ($history as $entry)
                        <div class="border-b border-line py-2.5 text-sm last:border-0">
                            <p class="text-ink">{{ str_replace(['.', '_'], ' ', ucfirst($entry->action)) }}</p>
                            <p class="text-xs text-muted">
                                {{ $entry->actor?->name ?? 'System' }} · {{ $entry->created_at->format('d M Y, H:i') }}
                                @if (! empty($entry->meta['reason'])) · {{ $entry->meta['reason'] }} @endif
                            </p>
                        </div>
                    @empty
                        <p class="mt-2 text-sm text-muted">No recorded changes yet.</p>
                    @endforelse
                </section>
            </div>
        @endif
    </div>
</x-layouts.admin>
