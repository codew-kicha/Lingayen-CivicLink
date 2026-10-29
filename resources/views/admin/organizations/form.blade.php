{{--
    The organization page. New organizations are registered in the dialog (register-dialog.blade.php).
    Every card uses the same header row (title, optional link) over a p-6 body, and the two columns
    start at the top (items-start), so a short column never stretches into an empty card.
--}}
<x-layouts.app :header="$organization->name"
                 :subheader="'Brgy. '.$organization->barangay.' · '.$organization->sector">
    @php $active = $organization->accreditations->firstWhere('status', 'active'); @endphp

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="card">
                @csrf
                @method('PATCH')

                <div class="border-b border-line px-6 py-4">
                    <h2 class="card-title">Organization details</h2>
                </div>

                <div class="space-y-6 p-6">
                    <div>
                        <label for="name" class="field-label">Organization name</label>
                        <input id="name" name="name" value="{{ old('name', $organization->name) }}" required
                               class="field-input @error('name') field-input-error @enderror"
                               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
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
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-line px-6 py-4">
                    <button type="submit" class="btn-primary">Save details</button>
                    <a href="{{ route('admin.organizations.index') }}" class="btn-ghost ml-auto">Back to organizations</a>
                </div>
            </form>

            <section class="card" aria-labelledby="timeline-title">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-6 py-4">
                    <div>
                        <h2 id="timeline-title" class="card-title">Activity, last 24 months</h2>
                        <p class="mt-1 text-sm text-muted">Verified activities per month, including those it joined as a partner.</p>
                    </div>
                    <x-source-legend class="ml-auto" with-unknown />
                </div>
                <div class="p-6">
                    @if ($timeline['longestGap'])
                        <p class="mb-4 inline-flex items-center gap-tight rounded-sm bg-warning-subtle px-3 py-2 text-sm text-warning">
                            <x-phosphor-warning class="h-4 w-4" aria-hidden="true" />
                            No verified activity from {{ $timeline['longestGap']['from']->format('M Y') }}
                            to {{ $timeline['longestGap']['to']->format('M Y') }} ({{ $timeline['longestGap']['months'] }} months)
                        </p>
                    @endif
                    <x-source-columns :series="$timeline['months']" height="6rem"
                                      :label="'Verified activities per month for '.$organization->name" />
                </div>
            </section>

            <section class="card" aria-labelledby="history-title">
                <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                    <h2 id="history-title" class="card-title">Recent history</h2>
                    <a href="{{ route('admin.audit.index', ['organization' => $organization->id]) }}" class="ml-auto whitespace-nowrap text-sm font-semibold text-primary hover:underline">Full history</a>
                </div>
                @if ($history->isEmpty())
                    <p class="p-6 text-sm text-muted">No recorded changes yet.</p>
                @else
                    <ul class="divide-y divide-line px-6">
                        @foreach ($history as $entry)
                            <li class="flex flex-wrap items-baseline gap-x-3 py-3 text-sm">
                                <span class="text-ink">{{ str_replace(['.', '_'], ' ', ucfirst($entry->action)) }}</span>
                                <span class="text-xs text-muted sm:ml-auto">
                                    {{ $entry->actor?->name ?? 'System' }} · {{ $entry->created_at->format('d M Y, H:i') }}
                                </span>
                                @if (! empty($entry->meta['reason']))
                                    <span class="basis-full text-xs text-muted">{{ $entry->meta['reason'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card" aria-labelledby="cleanup-title">
                <div class="border-b border-line px-6 py-4">
                    <h2 id="cleanup-title" class="card-title">Duplicate or mistaken record?</h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-muted">
                        Merge a duplicate into the record that should stay, or delete a test entry. To keep the record but
                        take it off the public site, use Hide on the Organizations list instead.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="button" class="btn-secondary" @click="$dispatch('open-dialog', 'merge-organization')">Merge into another</button>
                        <button type="button" class="btn-danger" @click="$dispatch('open-dialog', 'delete-organization')">
                            <x-phosphor-trash class="h-4 w-4" aria-hidden="true" />Delete organization
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card" aria-labelledby="account-title">
                <div class="border-b border-line px-6 py-4">
                    <h2 id="account-title" class="card-title">Representative account</h2>
                </div>
                <div class="p-6">
                    @if ($organization->user)
                        <div class="flex flex-wrap items-start gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $organization->user->name }}</p>
                                <p class="text-sm text-muted">{{ $organization->user->email }}{{ $organization->user->phone ? ' · '.$organization->user->phone : '' }}</p>
                            </div>
                            <span class="ml-auto">
                                @if (! $organization->user->is_active)
                                    <span class="badge-danger">Deactivated</span>
                                @elseif (! $organization->user->email_verified_at)
                                    <span class="badge-warning">Invitation pending</span>
                                @else
                                    <span class="badge-success">Active</span>
                                @endif
                            </span>
                        </div>
                        <a href="{{ route('admin.accounts.index', ['q' => $organization->user->email]) }}" class="btn-secondary mt-4">Manage account</a>
                    @else
                        <p class="text-sm text-muted">No one can sign in for this organization yet. Invite its representative:</p>
                        <form method="POST" action="{{ route('admin.organizations.account', $organization) }}" class="mt-4">
                            @csrf
                            <div class="space-y-4">
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
                            </div>
                            <button type="submit" class="btn-primary mt-6">Send invitation</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="card" aria-labelledby="accreditation-title">
                <div class="border-b border-line px-6 py-4">
                    <h2 id="accreditation-title" class="card-title">Accreditation</h2>
                </div>
                <div class="p-6">
                    @if ($active)
                        <p class="flex flex-wrap items-center gap-2">
                            <x-status-badge status="active" />
                            <span class="font-mono text-sm text-muted">{{ $active->verification_code }}</span>
                        </p>
                        <p class="mt-2 text-sm text-muted">
                            Valid until <span class="font-semibold text-ink">{{ $active->expires_at->format('d M Y') }}</span>
                            ({{ (int) now()->startOfDay()->diffInDays($active->expires_at, false) }} days left)
                        </p>

                        <details class="mt-3" @if ($errors->has('expires_at') || $errors->has('expiry_reason')) open @endif>
                            <summary class="btn-ghost btn-sm -ml-3 cursor-pointer list-none">
                                <x-phosphor-calendar-blank class="h-4 w-4" aria-hidden="true" />Change expiry date
                            </summary>
                            <form method="POST" action="{{ route('admin.organizations.expiry', $organization) }}" class="mt-3 space-y-3">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label for="expires_at" class="field-label">New expiry date</label>
                                    <input type="date" id="expires_at" name="expires_at" required
                                           value="{{ old('expires_at', $active->expires_at->toDateString()) }}"
                                           min="{{ now()->addDay()->toDateString() }}" max="{{ now()->addYears(10)->toDateString() }}"
                                           class="field-input @error('expires_at') field-input-error @enderror">
                                    @error('expires_at')<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="expiry_reason" class="field-label">Reason <span class="font-normal text-muted">(optional, kept in the audit log)</span></label>
                                    <input type="text" id="expiry_reason" name="expiry_reason" value="{{ old('expiry_reason') }}" maxlength="500"
                                           placeholder="e.g. Extended by SB Resolution No. 2026-41" class="field-input">
                                </div>
                                <button type="submit" class="btn-secondary">Save expiry date</button>
                            </form>
                        </details>

                        @php $canEmail = $organization->user?->email_verified_at && $organization->user->is_active; @endphp
                        <form method="POST" action="{{ route('admin.organizations.remind', $organization) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn-secondary" @disabled(! $canEmail)>
                                <x-phosphor-envelope-simple class="h-4 w-4" aria-hidden="true" />Send expiry reminder
                            </button>
                            <p class="field-hint">
                                @if (! $canEmail)
                                    No active representative account to email.
                                @elseif ($lastReminder)
                                    Last reminded {{ \Illuminate\Support\Carbon::parse($lastReminder)->format('d M Y, H:i') }}.
                                @else
                                    Emails the representative the expiry date and how to renew.
                                @endif
                            </p>
                        </form>

                        <details class="mt-4">
                            <summary class="btn-ghost -ml-3 cursor-pointer list-none px-3 text-danger">Revoke accreditation</summary>
                            <form method="POST" action="{{ route('admin.organizations.revoke', $organization) }}" class="mt-3 space-y-3">
                                @csrf
                                <label for="revoke-reason" class="field-label">Reason (kept in the audit log)</label>
                                <textarea id="revoke-reason" name="reason" rows="3" required minlength="10" class="field-input">{{ old('reason') }}</textarea>
                                @error('reason')<p class="field-error">{{ $message }}</p>@enderror
                                <button type="submit" class="btn-danger">Revoke {{ $active->verification_code }}</button>
                            </form>
                        </details>
                    @else
                        <p class="text-sm text-muted">
                            Not currently accredited.
                            @if ($organization->accreditations->isNotEmpty())
                                Last record: {{ ucfirst($organization->accreditations->first()->status) }}.
                            @endif
                        </p>
                    @endif
                </div>
            </section>

            <section class="card" aria-labelledby="encode-title">
                <div class="border-b border-line px-6 py-4">
                    <h2 id="encode-title" class="card-title">Encode on their behalf</h2>
                    <p class="mt-1 text-sm text-muted">For paper submissions brought to the office.</p>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="btn-secondary" @click="$dispatch('open-dialog', 'file-application')">
                            <x-phosphor-upload-simple class="h-4 w-4" aria-hidden="true" />File an application
                        </button>
                        @if ($active)
                            <button type="button" class="btn-secondary" @click="$dispatch('open-dialog', 'log-activity')">
                                <x-phosphor-plus class="h-4 w-4" aria-hidden="true" />Log an activity
                            </button>
                        @else
                            <button type="button" class="btn-secondary" disabled title="Only accredited organizations can log activities">Log an activity</button>
                        @endif
                    </div>

                    <p class="mt-6 text-sm font-semibold text-ink">Printable forms</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ([
                            [route('admin.organizations.members', $organization), 'Officers and members'],
                            [route('accreditation.form', 'new'), 'Blank form: new application'],
                            [route('accreditation.form', 'renewal'), 'Blank form: renewal'],
                        ] as [$url, $label])
                            <li>
                                <a href="{{ $url }}" class="inline-flex min-h-8 items-center gap-2 font-semibold text-primary hover:underline">
                                    <x-phosphor-file-pdf class="h-4 w-4 shrink-0" aria-hidden="true" />{{ $label }} (PDF)
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

        </div>
    </div>

    @include('admin.organizations.cleanup-dialogs')
    @include('admin.organizations.application-dialog')
    @if ($active)
        @include('admin.organizations.activity-dialog')
    @endif
</x-layouts.app>
