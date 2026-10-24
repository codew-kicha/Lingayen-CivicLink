{{--
    "Log an activity" dialog on the Activities page. ?log=1 opens it (the dashboard's button points
    there), and after a validation error the page comes back with it open and the typed values kept.
    A chosen sign-in sheet has to be attached again after an error; browsers clear files on reload.
--}}
@php
    $reopen = $errors->any() && old('_dialog') === 'log-activity';
@endphp

<x-dialog name="log-activity" title="Log an activity" width="md" :open="$reopen || request()->boolean('log')"
          subtitle="The Desk Office verifies each entry before it counts toward your score.">
    <form method="POST" action="{{ route('cso.activities.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
        @csrf
        <input type="hidden" name="_dialog" value="log-activity">

        <div class="dialog-body space-y-6">
            @include('activities.fields', ['activity' => null])
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-ghost" @click="close()">Cancel</button>
            <button type="submit" class="btn-primary">Log activity</button>
        </div>
    </form>
</x-dialog>
