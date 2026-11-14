@props(['organization' => null, 'user'])

{{--
    The member list (pre-final defense panel): at least 5 people, the first always the representative
    (prefilled from the account, position fixed). Five rows to a page so the page stays short; every
    row stays in the form, so all of them are submitted. A spreadsheet can fill the table: the template
    and the upload go through Cso\MemberListController, and the person reviews the rows before saving.
    Completely blank rows are not submitted. Posts members[i][last_name|first_name|middle_name|position|
    contact_number|email]; rules in App\Models\OrganizationMember::rules(). Logic: Alpine `memberTable`.
--}}
@php
    use App\Models\OrganizationMember;
    use App\Models\User;

    $parts = fn ($person) => $person->first_name || $person->last_name
        ? ['last_name' => $person->last_name, 'first_name' => $person->first_name, 'middle_name' => $person->middle_name]
        : array_intersect_key(User::splitName((string) $person->name), array_flip(['last_name', 'first_name', 'middle_name']));

    if (old('members') !== null) {
        $rows = collect(old('members'))->map(fn ($row, $key) => ['src' => $key] + $row)->values();
    } else {
        $rows = collect($organization?->members ?? [])->map(fn ($member) => $parts($member) + [
            'position' => $member->position, 'contact_number' => $member->contact_number, 'email' => $member->email,
        ]);
    }

    // The representative always comes first; a list without one starts from the account.
    $repIndex = $rows->search(fn ($row) => strcasecmp(trim((string) ($row['position'] ?? '')), OrganizationMember::REPRESENTATIVE) === 0);
    $rep = $repIndex === false
        ? $parts($user) + ['position' => OrganizationMember::REPRESENTATIVE, 'contact_number' => $user->phone, 'email' => $user->email]
        : $rows->pull($repIndex);
    $rows = $rows->prepend($rep)->values();

    $errorBag = collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, 'members.'))->all();
    $fields = [
        'last_name' => ['Last name', 'text', 'family-name'],
        'first_name' => ['First name', 'text', 'given-name'],
        'middle_name' => ['Middle name', 'text', 'additional-name'],
        'position' => ['Position', 'text', 'organization-title'],
        'contact_number' => ['Contact number', 'tel', 'tel'],
        'email' => ['Email', 'email', 'email'],
    ];
@endphp

<div x-data="memberTable(@js([
        'rows' => $rows->all(),
        'errors' => $errorBag,
        'minimum' => OrganizationMember::MINIMUM,
        'representative' => OrganizationMember::REPRESENTATIVE,
        'importUrl' => route('cso.members.import'),
    ]))" class="space-y-4">

    {{-- Count, spreadsheet tools --}}
    <div class="flex flex-wrap items-center gap-3">
        <p class="flex items-center gap-2 text-sm font-semibold" :class="filled >= minimum ? 'text-success' : 'text-warning'" aria-live="polite">
            <x-phosphor-users class="h-5 w-5" aria-hidden="true" />
            <span><span x-text="filled">{{ $rows->count() }}</span> of at least {{ OrganizationMember::MINIMUM }} members</span>
        </p>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <a href="{{ route('cso.members.template') }}" class="btn-ghost btn-sm">
                <x-phosphor-file-xls class="h-4 w-4" aria-hidden="true" />Excel template
            </a>
            <label class="btn-secondary btn-sm cursor-pointer focus-within:outline focus-within:outline-2 focus-within:outline-focus">
                <x-phosphor-upload-simple class="h-4 w-4" aria-hidden="true" />
                <span x-text="importing ? 'Reading the file...' : 'Upload an Excel list'">Upload an Excel list</span>
                <input type="file" class="sr-only" accept=".xlsx,.xls,.csv" @change="upload($event)" :disabled="importing">
            </label>
        </div>
    </div>

    @error('members')
        <p role="alert" class="rounded-sm border border-danger bg-danger-subtle px-4 py-3 text-sm text-danger">{{ $message }}</p>
    @enderror
    <div x-show="notice" x-cloak role="status" class="rounded-sm border px-4 py-3 text-sm"
         :class="noticeError ? 'border-danger bg-danger-subtle text-danger' : 'border-success/40 bg-success-subtle text-success'">
        <p x-text="notice"></p>
        <ul class="mt-1 list-disc pl-6" x-show="skipped.length">
            <template x-for="line in skipped" :key="line"><li x-text="line"></li></template>
        </ul>
    </div>

    <datalist id="member-positions">
        @foreach (OrganizationMember::POSITION_SUGGESTIONS as $position)
            <option value="{{ $position }}"></option>
        @endforeach
    </datalist>

    {{-- The rows: all in the form, five shown at a time --}}
    <ol class="divide-y divide-line rounded-md border border-line bg-paper" aria-label="Members">
        <template x-for="(row, index) in rows" :key="row.key">
            <li x-show="onPage(index)" class="grid gap-3 p-4 sm:grid-cols-[2rem_minmax(0,1fr)_2.75rem]">
                <p class="text-sm font-semibold tabular-nums text-muted">
                    <span x-text="index + 1"></span><span class="sr-only">.</span>
                </p>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($fields as $field => [$label, $type, $autocomplete])
                        <div>
                            <label :for="'member-' + row.key + '-{{ $field }}'" class="mb-1 block text-xs font-semibold text-muted">
                                {{ $label }}
                                @if (in_array($field, ['last_name', 'first_name'], true))
                                    <span class="sr-only">(required)</span>
                                @endif
                            </label>
                            <input type="{{ $type }}" :id="'member-' + row.key + '-{{ $field }}'"
                                   :name="blank(row) ? null : 'members[' + index + '][{{ $field }}]'"
                                   x-model="row.{{ $field }}" autocomplete="off" maxlength="{{ $field === 'email' ? 255 : 100 }}"
                                   @if ($field === 'position') list="member-positions" :readonly="row.locked" placeholder="Member" @endif
                                   :aria-invalid="error(row, '{{ $field }}') ? 'true' : null"
                                   class="field-input min-h-10 text-sm" :class="{ 'field-input-error': error(row, '{{ $field }}'), 'bg-canvas': row.locked && '{{ $field }}' === 'position' }">
                            <p class="field-error" x-show="error(row, '{{ $field }}')" x-text="error(row, '{{ $field }}')"></p>
                        </div>
                    @endforeach
                </div>
                <div class="flex sm:justify-end">
                    <button type="button" class="icon-btn" x-show="! row.locked" @click="remove(row)">
                        <x-phosphor-x class="h-4 w-4" aria-hidden="true" />
                        <span class="sr-only" x-text="'Remove member ' + (index + 1)"></span>
                    </button>
                    <span x-show="row.locked" class="pt-6 text-xs font-semibold text-muted" title="The representative is always on the list">
                        <x-phosphor-lock-simple class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Representative, always on the list</span>
                    </span>
                </div>
            </li>
        </template>
    </ol>

    {{-- Pages and adding --}}
    <div class="flex flex-wrap items-center gap-3">
        <button type="button" class="btn-secondary btn-sm" @click="add()">
            <x-phosphor-plus class="h-4 w-4" aria-hidden="true" />Add member
        </button>
        <nav class="ml-auto flex items-center gap-2" aria-label="Member pages" x-show="pages > 1" x-cloak>
            <button type="button" class="icon-btn" @click="page--" :disabled="page === 1">
                <x-phosphor-caret-left class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Previous page</span>
            </button>
            <template x-for="n in pages" :key="n">
                <button type="button" class="inline-flex h-9 min-w-9 items-center justify-center rounded-sm px-2 text-sm font-semibold tabular-nums"
                        :class="n === page ? 'bg-primary text-on-primary' : 'text-ink hover:bg-primary-subtle'"
                        :aria-current="n === page ? 'page' : null" @click="page = n" x-text="n"></button>
            </template>
            <button type="button" class="icon-btn" @click="page++" :disabled="page === pages">
                <x-phosphor-caret-right class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Next page</span>
            </button>
        </nav>
    </div>
</div>
