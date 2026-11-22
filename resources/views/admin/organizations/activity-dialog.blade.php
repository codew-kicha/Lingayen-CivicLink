{{--
    "Log an activity" dialog on the organization page: PESO staff encode an activity reported on paper
    or in person. ?log=1 opens it (the old Log activity page now points here), and after a validation
    error the page comes back with it open and the typed values kept. A chosen sign-in sheet has to be
    attached again after an error; browsers clear files on reload. Accredited organizations only.
--}}
@php
    $reopen = $errors->any() && old('_dialog') === 'log-activity';
@endphp

<x-dialog name="log-activity" title="Log an activity" width="md" :open="$reopen || request()->boolean('log')"
          :subtitle="'Reported on paper or in person by '.$organization->name.'. It joins the verification queue like any other.'">
    <form method="POST" action="{{ route('admin.organizations.activities.store', $organization) }}" enctype="multipart/form-data"
          class="flex min-h-0 flex-1 flex-col">
        @csrf
        <input type="hidden" name="_dialog" value="log-activity">

        <div class="dialog-body space-y-6">
            @if ($reopen)
                <p role="alert" class="rounded-sm border border-danger bg-danger-subtle px-4 py-3 text-sm text-danger">
                    Something needs fixing below. Attach the sign-in sheet again if you chose one; browsers clear files on reload.
                </p>
            @endif

            @include('activities.fields', ['activity' => null])
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-ghost" @click="close()">Cancel</button>
            <button type="submit" class="btn-primary">Log on their behalf</button>
        </div>
    </form>
</x-dialog>
