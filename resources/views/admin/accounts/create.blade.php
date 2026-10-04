<x-layouts.admin header="Add a PESO administrator"
                 subheader="They receive an email link to set their own password. You never see or choose it.">
    <form method="POST" action="{{ route('admin.accounts.store') }}" class="panel max-w-xl space-y-5 p-6">
        @csrf

        <div>
            <label for="name" class="field-label">Full name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="off"
                   class="field-input @error('name') field-input-error @enderror"
                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="off"
                   class="field-input @error('email') field-input-error @enderror"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="field-label">Mobile number <span class="font-normal text-muted">(optional)</span></label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="0917 123 4567"
                   class="field-input @error('phone') field-input-error @enderror"
                   @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
            @error('phone')<p id="phone-error" class="field-error">{{ $message }}</p>@enderror
        </div>

        <p class="text-sm text-muted">
            Administrators can review applications, manage every account, and revoke accreditations.
            Only add staff of the Civil Society Desk Office.
        </p>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Send invitation</button>
            <a href="{{ route('admin.accounts.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
