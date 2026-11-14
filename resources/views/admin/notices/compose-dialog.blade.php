{{--
    "Send a notice" dialog (Admin\NoticeController@store). On the Notices page it asks who to send to;
    on an organization page ($organization set) it goes to that organization only. A live count says how
    many accounts the audience reaches. After a validation error the page comes back with it open.
--}}
@php
    use App\Models\OfficeNotice;
    use App\Models\Organization;

    $organization ??= null;
    $reopen = $errors->any() && old('_dialog') === 'send-notice';
    $audience = old('audience', $organization ? 'organization' : 'accredited');
    $value = old('audience_value', $organization?->id);
    $choices = $organization ? collect() : Organization::whereNotNull('user_id')->orderBy('name')->get(['id', 'name', 'barangay']);
@endphp

<x-dialog name="send-notice" title="Send a notice" width="md" :open="$reopen"
          :subtitle="$organization ? 'To '.$organization->name.'. It appears under their Notifications.' : 'It appears under Notifications for every organization you choose.'">
    <form method="POST" action="{{ route('admin.notices.store') }}" class="flex min-h-0 flex-1 flex-col"
          x-data="{
              audience: @js($audience),
              // One field per kind of audience, so the three lists never fight over a shared value.
              sector: @js($audience === 'sector' ? (string) $value : ''),
              barangay: @js($audience === 'barangay' ? (string) $value : ''),
              org: @js($audience === 'organization' ? (string) $value : ''),
              count: null,
              asked: 0, // only the newest answer counts, whatever order they come back in
              get value() { return { sector: this.sector, barangay: this.barangay, organization: this.org }[this.audience] ?? ''; },
              async recount() {
                  const ask = ++this.asked;
                  const needsValue = ['organization', 'sector', 'barangay'].includes(this.audience);
                  if (needsValue && ! this.value) { this.count = null; return; }
                  const query = new URLSearchParams({ audience: this.audience, audience_value: needsValue ? this.value : '' });
                  const response = await fetch(@js(route('admin.notices.count')) + '?' + query, { headers: { Accept: 'application/json' } });
                  const count = response.ok ? (await response.json()).recipients : null;
                  if (ask === this.asked) this.count = count;
              },
          }"
          x-init="recount()">
        @csrf
        <input type="hidden" name="_dialog" value="send-notice">

        <div class="dialog-body space-y-6">
            @if ($organization)
                <input type="hidden" name="audience" value="organization">
                <input type="hidden" name="audience_value" value="{{ $organization->id }}">
            @else
                <div>
                    <label for="notice-audience" class="field-label">Send to</label>
                    <select id="notice-audience" name="audience" x-model="audience" @change="$nextTick(() => recount())"
                            class="field-input @error('audience') field-input-error @enderror">
                        @foreach (OfficeNotice::AUDIENCES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('audience')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div x-show="audience === 'sector'" x-cloak>
                    <label for="notice-sector" class="field-label">Sector</label>
                    <select id="notice-sector" x-model="sector" @change="$nextTick(() => recount())" class="field-input">
                        <option value="">Choose a sector</option>
                        @foreach (config('sectors') as $sector)
                            <option>{{ $sector }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="audience === 'barangay'" x-cloak>
                    <label for="notice-barangay" class="field-label">Barangay</label>
                    <select id="notice-barangay" x-model="barangay" @change="$nextTick(() => recount())" class="field-input">
                        <option value="">Choose a barangay</option>
                        @foreach (config('barangays') as $barangay)
                            <option>{{ $barangay }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="audience === 'organization'" x-cloak>
                    <label for="notice-organization" class="field-label">Organization</label>
                    <select id="notice-organization" x-model="org" @change="$nextTick(() => recount())" class="field-input">
                        <option value="">Choose an organization</option>
                        @foreach ($choices as $choice)
                            <option value="{{ $choice->id }}">{{ $choice->name }} · Brgy. {{ $choice->barangay }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="audience_value" :value="value">
                @error('audience_value')<p class="field-error">{{ $message }}</p>@enderror
            @endif

            <p class="flex items-center gap-2 text-sm" aria-live="polite" x-show="count !== null" x-cloak>
                <x-phosphor-users class="h-4 w-4 text-muted" aria-hidden="true" />
                <span :class="count ? 'text-ink' : 'text-warning'"
                      x-text="count ? 'Reaches ' + count + ' organization' + (count === 1 ? '' : 's') + '.' : 'No organization account matches this yet.'"></span>
            </p>

            <div>
                <label for="notice-title" class="field-label">Title</label>
                <input id="notice-title" name="title" value="{{ old('title') }}" required maxlength="150"
                       placeholder="e.g. CSO Assembly on 15 December"
                       class="field-input @error('title') field-input-error @enderror">
                @error('title')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="notice-body" class="field-label">Message</label>
                <textarea id="notice-body" name="body" rows="6" required maxlength="5000"
                          class="field-input @error('body') field-input-error @enderror">{{ old('body') }}</textarea>
                @error('body')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-start gap-3 text-sm text-ink">
                <input type="checkbox" name="email" value="1" @checked(old('email')) class="mt-hair h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary">
                <span>Also email it <span class="block text-muted">To each representative whose email is confirmed. Emails go out after you send.</span></span>
            </label>
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-ghost" @click="close()">Cancel</button>
            <button type="submit" class="btn-primary">
                <x-phosphor-paper-plane-right class="h-4 w-4" aria-hidden="true" />Send notice
            </button>
        </div>
    </form>
</x-dialog>
