{{--
    Who a notice goes to: every organization, the accredited ones, a sector, a barangay or one
    organization, with a live count of the accounts it reaches. Posts `audience` and `audience_value`.
    Used by "Send a notice" and by an LGU event's announcement. Expects $choices (organizations with
    an account) and optionally $idPrefix, $audience, $value.
--}}
@php
    use App\Models\OfficeNotice;

    $idPrefix ??= 'notice';
    $audience ??= old('audience', 'accredited');
    $value ??= old('audience_value');
@endphp

<div class="space-y-6"
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
    <div>
        <label for="{{ $idPrefix }}-audience" class="field-label">Send to</label>
        <select id="{{ $idPrefix }}-audience" name="audience" x-model="audience" @change="$nextTick(() => recount())"
                class="field-input @error('audience') field-input-error @enderror">
            @foreach (OfficeNotice::AUDIENCES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('audience')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div x-show="audience === 'sector'" x-cloak>
        <label for="{{ $idPrefix }}-sector" class="field-label">Sector</label>
        <select id="{{ $idPrefix }}-sector" x-model="sector" @change="$nextTick(() => recount())" class="field-input">
            <option value="">Choose a sector</option>
            @foreach (config('sectors') as $sector)
                <option>{{ $sector }}</option>
            @endforeach
        </select>
    </div>
    <div x-show="audience === 'barangay'" x-cloak>
        <label for="{{ $idPrefix }}-barangay" class="field-label">Barangay</label>
        <select id="{{ $idPrefix }}-barangay" x-model="barangay" @change="$nextTick(() => recount())" class="field-input">
            <option value="">Choose a barangay</option>
            @foreach (config('barangays') as $barangay)
                <option>{{ $barangay }}</option>
            @endforeach
        </select>
    </div>
    <div x-show="audience === 'organization'" x-cloak>
        <label for="{{ $idPrefix }}-organization" class="field-label">Organization</label>
        <select id="{{ $idPrefix }}-organization" x-model="org" @change="$nextTick(() => recount())" class="field-input">
            <option value="">Choose an organization</option>
            @foreach ($choices as $choice)
                <option value="{{ $choice->id }}">{{ $choice->name }} · Brgy. {{ $choice->barangay }}</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="audience_value" :value="value">
    @error('audience_value')<p class="field-error">{{ $message }}</p>@enderror

    <p class="flex items-center gap-2 text-sm" aria-live="polite" x-show="count !== null" x-cloak>
        <x-phosphor-users class="h-4 w-4 text-muted" aria-hidden="true" />
        <span :class="count ? 'text-ink' : 'text-warning'"
              x-text="count ? 'Reaches ' + count + ' organization' + (count === 1 ? '' : 's') + '.' : 'No organization account matches this yet.'"></span>
    </p>
</div>
