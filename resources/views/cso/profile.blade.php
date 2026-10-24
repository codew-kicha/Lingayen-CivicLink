{{--
    Organization profile as a settings page (the GitHub organization profile / Stripe business
    details pattern): each section has its title and a line of explanation on the left and its
    fields in a card on the right. The public section shows a live preview of the directory card.
    Members are an editable table: saved rows render on the server (so the form still works without
    JavaScript), rows added here come from Alpine. A save bar stays pinned to the bottom.
--}}
@php
    $members = old('members', $organization?->members->map->only(['name', 'position', 'contact_number', 'email'])->all() ?? []);
    if (! $members) {
        $members = [['name' => '', 'position' => '', 'contact_number' => '', 'email' => '']];
    }
    $nextIndex = (int) max(array_keys($members)) + 1;
    $logoUrl = $organization?->logo_path ? asset('storage/'.$organization->logo_path) : null;
@endphp

<x-layouts.app header="Organization profile"
               :subheader="$organization ? 'How your organization appears to the public, and the officer list only the Desk Office sees.' : 'Tell the Desk Office about your organization. You can apply for accreditation once this is saved.'">
    <form method="POST" action="{{ route('cso.profile.update') }}" enctype="multipart/form-data"
          x-data="{
              dirty: false,
              logoUrl: @js($logoUrl),
              removeLogo: false,
              added: [],
              next: {{ $nextIndex }},
              preview: { name: '', sector: '', barangay: '', advocacy: '' },
              sync() {
                  const f = this.$root.elements, v = (n) => f.namedItem(n)?.value ?? '';
                  this.preview = { name: v('name'), sector: v('sector'), barangay: v('barangay'), advocacy: v('advocacy') };
              },
              pickLogo(event) {
                  const file = event.target.files[0];
                  if (! file) return;
                  this.logoUrl = URL.createObjectURL(file);
                  this.removeLogo = false;
              },
              clearLogo() {
                  this.$refs.logo.value = '';
                  this.logoUrl = null;
                  this.removeLogo = true;
                  this.dirty = true;
              },
              addMember() {
                  this.added.push(this.next++);
                  this.dirty = true;
                  this.$nextTick(() => document.getElementById('member-name-' + this.added.at(-1)).focus());
              },
          }"
          x-init="$nextTick(() => sync())" @input="dirty = true; sync()" @change="dirty = true; sync()">
        @csrf
        @method('PATCH')
        <input type="hidden" name="remove_logo" :value="removeLogo ? 1 : 0" value="0">

        {{-- Public listing --}}
        <section class="grid gap-6 border-b border-line pb-8 lg:grid-cols-[14rem_minmax(0,1fr)]" aria-labelledby="public-title">
            <div>
                <h2 id="public-title" class="card-title">Public listing</h2>
                <p class="mt-1 text-sm text-muted">Shown in the public directory once your organization is accredited.</p>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="card space-y-6 p-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-md border border-line bg-paper">
                            <img x-show="logoUrl" :src="logoUrl" src="{{ $logoUrl }}" alt="" class="h-full w-full object-contain p-1" @unless ($logoUrl) x-cloak @endunless>
                            <x-phosphor-buildings x-show="! logoUrl" class="h-8 w-8 text-muted" aria-hidden="true" :x-cloak="(bool) $logoUrl" />
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="file" id="logo" name="logo" x-ref="logo" accept="image/jpeg,image/png,image/webp"
                                       class="peer sr-only" @change="pickLogo($event)">
                                <label for="logo" class="btn-secondary btn-sm cursor-pointer peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-focus">
                                    <x-phosphor-image class="h-4 w-4" aria-hidden="true" /><span x-text="logoUrl ? 'Change logo' : 'Upload logo'">Upload logo</span>
                                </label>
                                <button type="button" class="btn-ghost btn-sm" x-show="logoUrl" x-cloak @click="clearLogo()">Remove</button>
                            </div>
                            <p class="field-hint">JPG, PNG or WebP, up to 2 MB. Square images look best.</p>
                            @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="name" class="field-label">Organization name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $organization?->name) }}" required maxlength="255"
                               class="field-input @error('name') field-input-error @enderror"
                               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="sector" class="field-label">Sector</label>
                            <select id="sector" name="sector" required
                                    class="field-input @error('sector') field-input-error @enderror"
                                    @error('sector') aria-invalid="true" aria-describedby="sector-error" @enderror>
                                <option value="">Choose a sector</option>
                                @foreach ($sectors as $sector)
                                    <option value="{{ $sector }}" @selected(old('sector', $organization?->sector) === $sector)>{{ $sector }}</option>
                                @endforeach
                            </select>
                            @error('sector')<p id="sector-error" class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <x-barangay-combobox :value="old('barangay', $organization?->barangay)" :error="$errors->first('barangay') ?: null" :marked="false" />
                    </div>

                    <div x-data="{ count: {{ mb_strlen((string) old('advocacy', $organization?->advocacy)) }} }">
                        <label for="advocacy" class="field-label">Advocacy <span class="font-normal text-muted">(optional)</span></label>
                        <textarea id="advocacy" name="advocacy" rows="4" maxlength="2000" @input="count = $el.value.length"
                                  placeholder="What your organization works on and who it serves"
                                  aria-describedby="advocacy-count"
                                  class="field-input @error('advocacy') field-input-error @enderror">{{ old('advocacy', $organization?->advocacy) }}</textarea>
                        <p id="advocacy-count" class="field-hint flex"><span>One or two sentences reads best.</span><span class="ml-auto tabular-nums" x-text="count + ' / 2000'"></span></p>
                        @error('advocacy')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Live preview of the directory card (resources/views/public/directory.blade.php). --}}
                <aside class="hidden xl:sticky xl:top-8 xl:block" aria-label="Preview of your directory card">
                    <p class="mb-2 text-sm font-semibold text-muted">Preview in the directory</p>
                    <div class="card p-6">
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-line bg-paper">
                                <img x-show="logoUrl" :src="logoUrl" alt="" class="h-full w-full object-contain p-1" x-cloak>
                                <x-phosphor-buildings x-show="! logoUrl" class="h-6 w-6 text-brand-700" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-ink" x-text="preview.name || 'Your organization name'"></p>
                                <p class="mt-1 text-sm text-muted"
                                   x-text="(preview.barangay ? 'Brgy. ' + preview.barangay : 'Barangay') + ', ' + (preview.sector || 'Sector')"></p>
                            </div>
                        </div>
                        <p class="mt-4 line-clamp-3 text-sm text-muted" x-show="preview.advocacy" x-text="preview.advocacy"></p>
                    </div>
                </aside>
            </div>
        </section>

        {{-- Structure --}}
        <section class="grid gap-6 border-b border-line py-8 lg:grid-cols-[14rem_minmax(0,1fr)]" aria-labelledby="structure-title">
            <div>
                <h2 id="structure-title" class="card-title">Structure</h2>
                <p class="mt-1 text-sm text-muted">The positions in your organization, one per line.</p>
            </div>
            <div class="card p-6 xl:max-w-[calc(100%-19.5rem)]">
                <label for="org_chart" class="field-label">Organizational structure <span class="font-normal text-muted">(optional)</span></label>
                <textarea id="org_chart" name="org_chart" rows="4" maxlength="2000" class="field-input @error('org_chart') field-input-error @enderror"
                          placeholder="President&#10;Vice President&#10;Secretary&#10;Treasurer">{{ old('org_chart', $organization?->org_chart) }}</textarea>
                @error('org_chart')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- Officers and members (private) --}}
        <section class="grid gap-6 py-8 lg:grid-cols-[14rem_minmax(0,1fr)]" aria-labelledby="members-title">
            <div>
                <h2 id="members-title" class="card-title">Officers and members</h2>
                <p class="mt-1 flex items-start gap-tight text-sm text-muted">
                    <x-phosphor-lock-simple class="mt-px h-4 w-4 shrink-0" aria-hidden="true" />
                    Visible to the Desk Office only, never public.
                </p>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table class="table-stack w-full">
                        <caption class="sr-only">Officers and members. Rows without a name are skipped.</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head w-1/3 pl-6">Name</th>
                                <th scope="col" class="table-head">Position</th>
                                <th scope="col" class="table-head">Contact number</th>
                                <th scope="col" class="table-head">Email</th>
                                <th scope="col" class="table-head w-12 pr-6"><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $i => $member)
                                <tr x-data="{ gone: false }" x-show="! gone">
                                    @foreach (['name' => 'Name', 'position' => 'Position', 'contact_number' => 'Contact number', 'email' => 'Email'] as $field => $label)
                                        <td @class(['table-cell align-top', 'pl-6' => $loop->first])>
                                            <span class="mb-1 block text-xs font-semibold text-muted sm:hidden" aria-hidden="true">{{ $label }}</span>
                                            <input type="{{ $field === 'email' ? 'email' : 'text' }}" id="member-{{ str_replace('_', '-', $field) }}-{{ $i }}"
                                                   name="members[{{ $i }}][{{ $field }}]" value="{{ $member[$field] ?? '' }}" :disabled="gone"
                                                   aria-label="{{ $label }}, row {{ $loop->parent->iteration }}"
                                                   class="field-input @error("members.{$i}.{$field}") field-input-error @enderror">
                                            @error("members.{$i}.{$field}")<p class="field-error">{{ $message }}</p>@enderror
                                        </td>
                                    @endforeach
                                    <td class="table-cell pr-6 align-top">
                                        <button type="button" class="icon-btn" @click="gone = true; dirty = true" x-cloak>
                                            <x-phosphor-x class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Remove row {{ $loop->iteration }}</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            <template x-for="i in added" :key="i">
                                <tr>
                                    @foreach (['name' => 'Name', 'position' => 'Position', 'contact_number' => 'Contact number', 'email' => 'Email'] as $field => $label)
                                        <td @class(['table-cell align-top', 'pl-6' => $loop->first])>
                                            <span class="mb-1 block text-xs font-semibold text-muted sm:hidden" aria-hidden="true">{{ $label }}</span>
                                            <input type="{{ $field === 'email' ? 'email' : 'text' }}" :id="'member-{{ str_replace('_', '-', $field) }}-' + i"
                                                   :name="'members[' + i + '][{{ $field }}]'" aria-label="{{ $label }}, new row" class="field-input">
                                        </td>
                                    @endforeach
                                    <td class="table-cell pr-6 align-top">
                                        <button type="button" class="icon-btn" @click="added = added.filter(n => n !== i); dirty = true">
                                            <x-phosphor-x class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Remove this row</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line px-6 py-4" x-cloak>
                    <button type="button" class="btn-ghost btn-sm -ml-3" @click="addMember()">
                        <x-phosphor-plus class="h-4 w-4" aria-hidden="true" />Add member
                    </button>
                </div>
            </div>
        </section>

        {{-- Save bar, pinned to the bottom of the window. --}}
        <div class="sticky bottom-0 z-sticky -mx-4 flex flex-wrap items-center gap-3 border-t border-line bg-paper/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <p class="mr-auto text-sm" :class="dirty ? 'font-semibold text-warning' : 'text-muted'"
               x-text="dirty ? 'You have unsaved changes' : 'All changes saved'">Save your changes when you're done.</p>
            <a href="{{ route('cso.profile.edit') }}" class="btn-ghost" x-show="dirty" x-cloak>Discard</a>
            <button type="submit" class="btn-primary">{{ $organization ? 'Save profile' : 'Create profile' }}</button>
        </div>
    </form>
</x-layouts.app>
