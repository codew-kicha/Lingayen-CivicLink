{{--
    Merge and Delete dialogs on an organization's page (560px each). Needs $organization,
    $footprint (counts from OrganizationRecords::footprint) and $mergeOptions ([{ id, name }]).
--}}
@php
    $reopenMerge = $errors->any() && old('_dialog') === 'merge-organization';
    $reopenDelete = $errors->any() && old('_dialog') === 'delete-organization';
    $counts = collect([
        'application' => $footprint['applications'],
        'accreditation' => $footprint['accreditations'],
        'activity' => $footprint['activities'],
        'document' => $footprint['documents'],
        'officer and member' => $footprint['members'],
    ])->filter()->map(fn ($count, $noun) => $count.' '.($noun === 'officer and member' ? Str::plural('officer/member', $count) : Str::plural($noun, $count)));
    $official = $footprint['accreditations'] > 0 || $organization->activities()->where('status', 'verified')->exists();
@endphp

<x-dialog name="merge-organization" title="Merge into another organization" width="sm" :open="$reopenMerge">
    <form method="POST" action="{{ route('admin.organizations.merge', $organization) }}" class="flex min-h-0 flex-1 flex-col"
          x-data="{ keep: @js($reopenMerge && old('into') ? [(int) old('into')] : []) }">
        @csrf
        <input type="hidden" name="_dialog" value="merge-organization">
        <input type="hidden" name="into" :value="keep[0] ?? ''">

        <div class="dialog-body space-y-6">
            <p class="text-sm text-ink">
                Use this when <span class="font-semibold">{{ $organization->name }}</span> is a duplicate. Its history moves to the
                organization you choose, then this record is deleted.
            </p>

            <div x-data="tagPicker(@js($mergeOptions), true)" x-modelable="selected" x-model="keep" class="relative" @focusout="settle()">
                <label for="merge-into" class="field-label field-required">Organization to keep</label>
                <div @class(['flex min-h-11 flex-wrap items-center gap-2 rounded-sm border bg-paper px-2 py-1 focus-within:border-primary hover:border-primary/60',
                             'border-danger' => $reopenMerge && $errors->has('into'), 'border-line-strong' => ! ($reopenMerge && $errors->has('into'))])>
                    <template x-for="organization in chosen" :key="organization.id">
                        <span class="inline-flex min-h-8 items-center gap-1 rounded-sm bg-primary-subtle pl-2 text-sm text-ink">
                            <span x-text="organization.name"></span>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-muted hover:text-danger touch:h-11 touch:w-11"
                                    @click="remove(organization.id)">
                                <x-phosphor-x class="h-4 w-4" aria-hidden="true" />
                                <span class="sr-only" x-text="'Remove ' + organization.name"></span>
                            </button>
                        </span>
                    </template>
                    <input id="merge-into" x-ref="input" x-model="query" x-show="! chosen.length" autocomplete="off" autofocus
                           role="combobox" aria-autocomplete="list" aria-controls="merge-into-list"
                           :aria-expanded="expanded.toString()"
                           :aria-activedescendant="expanded && matches[active] ? 'merge-org-' + matches[active].id : null"
                           @focus="expanded = true" @input="expanded = true; active = 0"
                           @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter="addActive($event)"
                           @keydown.escape="if (expanded) { expanded = false; $event.stopPropagation() }"
                           placeholder="Search by name"
                           @if ($reopenMerge && $errors->has('into')) aria-invalid="true" aria-describedby="merge-into-error" @endif
                           class="min-h-9 min-w-40 flex-1 border-0 bg-transparent px-1 text-base text-ink placeholder:text-placeholder focus:ring-0">
                </div>
                <ul id="merge-into-list" role="listbox" aria-label="Organizations" x-show="expanded && matches.length && ! chosen.length" x-cloak class="listbox">
                    <template x-for="(organization, i) in matches" :key="organization.id">
                        <li role="option" :id="'merge-org-' + organization.id" aria-selected="false"
                            :data-active="i === active ? '' : null" @mousedown.prevent="add(organization)" @mousemove="active = i"
                            class="listbox-option" x-text="organization.name"></li>
                    </template>
                </ul>
                @if ($reopenMerge && $errors->has('into'))<p id="merge-into-error" class="field-error">{{ $errors->first('into') }}</p>@endif
            </div>

            <div class="rounded-sm border border-line bg-canvas px-4 py-3 text-sm text-ink">
                <p class="font-semibold">What moves</p>
                <ul class="mt-2 list-disc space-y-1 pl-6 text-muted">
                    <li>{{ $counts->isEmpty() ? 'No applications, activities or documents yet.' : $counts->join(', ', ' and ').'.' }}</li>
                    <li>Partner and news tags, without counting anything twice.</li>
                    <li>If both are accredited, the kept organization's accreditation stays active and this one's is marked expired.</li>
                    <li>
                        @if ($organization->user)
                            The login {{ $organization->user->email }} moves across if the kept organization has none; otherwise it is deactivated.
                        @else
                            This record has no login.
                        @endif
                    </li>
                </ul>
            </div>

            <x-admin-password-field id="merge-admin-password" :error="$reopenMerge ? $errors->first('admin_password') : null" />
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-secondary" @click="close()">Cancel</button>
            <button type="submit" class="btn-danger" :disabled="! keep.length">Merge and delete this record</button>
        </div>
    </form>
</x-dialog>

<x-dialog name="delete-organization" :title="'Delete '.$organization->name.'?'" width="sm" :open="$reopenDelete">
    <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}" class="flex min-h-0 flex-1 flex-col">
        @csrf
        @method('DELETE')
        <input type="hidden" name="_dialog" value="delete-organization">

        <div class="dialog-body space-y-6">
            <div class="rounded-sm border border-danger bg-danger-subtle px-4 py-3 text-sm text-danger">
                <p class="font-semibold">This permanently deletes:</p>
                <ul class="mt-2 list-disc space-y-1 pl-6">
                    <li>The organization record{{ $counts->isNotEmpty() ? ', with '.$counts->join(', ', ' and ') : '' }}.</li>
                    <li>Uploaded documents and sign-in sheets.</li>
                    @if ($organization->user)
                        <li>The representative's login, {{ $organization->user->email }}.</li>
                    @endif
                </ul>
                <p class="mt-2">It cannot be undone. The audit log keeps its name and what was removed.</p>
            </div>

            @if ($official)
                <p class="text-sm text-ink">
                    This organization has an official record (an accreditation or verified activities). If it is a duplicate,
                    <button type="button" class="entity-link text-primary underline" @click="close(); $dispatch('open-dialog', 'merge-organization')">merge it instead</button>
                    so the history is kept. To only take it off the public site, use Hide on the Organizations list.
                </p>
            @endif

            <x-admin-password-field id="delete-admin-password" :error="$reopenDelete ? $errors->first('admin_password') : null" autofocus />
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-secondary" @click="close()">Cancel</button>
            <button type="submit" class="btn-danger"><x-phosphor-trash class="h-4 w-4" aria-hidden="true" />Delete permanently</button>
        </div>
    </form>
</x-dialog>
