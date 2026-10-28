<x-layouts.app header="Edit activity"
               subheader="You can correct or withdraw an activity until the Desk Office verifies it. Partners are credited once it is verified.">
    <form method="POST" action="{{ route('cso.activities.update', $activity) }}" enctype="multipart/form-data"
          class="panel max-w-2xl space-y-6 p-6">
        <p class="text-sm text-muted">All fields required unless marked optional.</p>
        @csrf
        @method('PUT')

        @include('activities.fields')

        <div class="flex flex-wrap items-center gap-3 border-t border-line pt-6">
            <button type="button" class="btn-danger mr-auto"
                    @click="$dispatch('withdraw-activity', @js(['title' => $activity->title, 'url' => route('cso.activities.destroy', $activity)]))">
                Withdraw activity
            </button>
            <a href="{{ route('cso.activities.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save changes</button>
        </div>
    </form>

    @include('cso.activities.withdraw-dialog')
</x-layouts.app>
