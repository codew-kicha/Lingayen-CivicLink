{{-- Activity fields shared by the CSO log form and PESO's assisted encoding. Needs $partnerOptions (id => name). --}}
<div>
    <label for="title" class="field-label">Activity title</label>
    <input type="text" id="title" name="title" value="{{ old('title') }}"
           class="field-input @error('title') field-input-error @enderror"
           @error('title') aria-invalid="true" aria-describedby="title-error" @enderror required>
    @error('title')<p id="title-error" class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="activity_date" class="field-label">Date held</label>
    <input type="date" id="activity_date" name="activity_date" value="{{ old('activity_date') }}"
           max="{{ now()->toDateString() }}"
           class="field-input @error('activity_date') field-input-error @enderror"
           @error('activity_date') aria-invalid="true" aria-describedby="activity_date-error" @enderror required>
    @error('activity_date')<p id="activity_date-error" class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="participants_estimate" class="field-label">
        Residents reached <span class="font-normal text-muted">(optional)</span>
    </label>
    <input type="number" id="participants_estimate" name="participants_estimate"
           value="{{ old('participants_estimate') }}" min="0"
           class="field-input @error('participants_estimate') field-input-error @enderror">
    <p class="field-hint">An estimate is fine.</p>
    @error('participants_estimate')<p class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="description" class="field-label">What took place</label>
    <textarea id="description" name="description" rows="4"
              class="field-input @error('description') field-input-error @enderror"
              @error('description') aria-invalid="true" aria-describedby="description-error" @enderror required>{{ old('description') }}</textarea>
    @error('description')<p id="description-error" class="field-error">{{ $message }}</p>@enderror
</div>

<fieldset>
    <legend class="field-label">Who organized it?</legend>
    <div class="space-y-2">
        @foreach ([
            'independent' => 'Our organization planned and ran it',
            'lgu_organized' => 'The LGU organized it, and we took part',
        ] as $value => $label)
            <label class="flex items-start gap-3 rounded-sm border border-line-strong p-3
                          hover:border-navy-300 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50">
                <input type="radio" name="activity_source" value="{{ $value }}" class="mt-0.5"
                       @checked(old('activity_source') === $value) required>
                <span class="text-sm text-ink">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    <p class="field-hint">PESO confirms this when verifying the activity.</p>
    @error('activity_source')<p class="field-error">{{ $message }}</p>@enderror
</fieldset>

@if ($partnerOptions)
    <fieldset x-data="{ q: '', picked: @js(array_map('intval', old('partners', []))) }">
        <legend class="field-label">Partner organizations <span class="font-normal text-muted">(optional)</span></legend>
        <p class="field-hint mt-0">Organizations that ran this with you. Each is credited once PESO verifies the activity.</p>
        <label for="partner-search" class="sr-only">Search organizations</label>
        <input type="search" id="partner-search" x-model="q" placeholder="Search by name" class="field-input mt-2">
        <div class="mt-2 max-h-48 overflow-y-auto rounded-sm border border-line">
            @foreach ($partnerOptions as $id => $name)
                <label class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm text-ink hover:bg-navy-50"
                       x-show="! q || @js(mb_strtolower($name)).includes(q.toLowerCase())">
                    <input type="checkbox" name="partners[]" value="{{ $id }}" x-model.number="picked">
                    {{ $name }}
                </label>
            @endforeach
        </div>
        <p class="field-hint" x-cloak x-show="picked.length" x-text="picked.length + ' selected'"></p>
        @error('partners')<p class="field-error">{{ $message }}</p>@enderror
        @error('partners.*')<p class="field-error">{{ $message }}</p>@enderror
    </fieldset>
@endif
