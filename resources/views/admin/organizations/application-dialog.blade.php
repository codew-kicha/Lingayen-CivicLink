{{--
    "File an application" dialog on the organization page: PESO staff upload a paper submission's
    scanned requirements. ?file=1 opens it (the old Encode application page now points here), and
    after a validation error the page comes back with it open. Browsers never keep chosen files
    across a reload, so that case asks for the files again.
--}}
@php
    $reopen = $errors->any() && old('_dialog') === 'file-application';
@endphp

<x-dialog name="file-application" title="File an application" width="lg" :open="$reopen || request()->boolean('file')"
          :subtitle="'Paper submission for '.$organization->name.'. It joins the review queue marked as assisted.'">
    <form method="POST" action="{{ route('admin.organizations.applications.store', $organization) }}"
          enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
        @csrf
        <input type="hidden" name="_dialog" value="file-application">

        <div class="dialog-body">
            @if ($reopen)
                <p role="alert" class="mb-6 rounded-sm border border-danger bg-danger-subtle px-4 py-3 text-sm text-danger">
                    Something needs fixing below. Attach the files again before submitting; browsers clear them on reload.
                </p>
            @endif

            @include('applications.fields')
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-ghost" @click="close()">Cancel</button>
            <button type="submit" class="btn-primary">Submit on their behalf</button>
        </div>
    </form>
</x-dialog>
