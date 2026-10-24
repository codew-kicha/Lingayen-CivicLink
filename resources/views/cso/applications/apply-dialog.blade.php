{{--
    "Apply for accreditation" dialog on the Applications page. ?apply=1 opens it (the dashboard, a
    rejected application and the old /applications/create address all point there), and after a
    validation error the page comes back with it open. Browsers never keep chosen files across a
    reload, so that case asks for the files again.
--}}
@php
    $reopen = $errors->any() && old('_dialog') === 'apply';
@endphp

<x-dialog name="apply" title="Apply for accreditation" width="lg" :open="$reopen || request()->boolean('apply')"
          subtitle="Choose the application type, then upload each requirement. The Desk Office reviews it and you are notified of any change.">
    <form method="POST" action="{{ route('cso.applications.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
        @csrf
        <input type="hidden" name="_dialog" value="apply">

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
            <button type="submit" class="btn-primary">Submit application</button>
        </div>
    </form>
</x-dialog>
