{{--
    "Send a notice" dialog (Admin\NoticeController@store). On the Notices page it asks who to send to;
    on an organization page ($organization set) it goes to that organization only. A live count says how
    many accounts the audience reaches (admin.notices.audience-fields). After a validation error the page
    comes back with it open.
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
    <form method="POST" action="{{ route('admin.notices.store') }}" class="flex min-h-0 flex-1 flex-col">
        @csrf
        <input type="hidden" name="_dialog" value="send-notice">

        <div class="dialog-body space-y-6">
            @if ($organization)
                <input type="hidden" name="audience" value="organization">
                <input type="hidden" name="audience_value" value="{{ $organization->id }}">
            @else
                @include('admin.notices.audience-fields', ['audience' => $audience, 'value' => $value, 'choices' => $choices])
            @endif

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
