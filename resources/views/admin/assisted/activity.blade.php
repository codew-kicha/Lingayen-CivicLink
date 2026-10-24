<x-layouts.app :header="'Log activity: '.$organization->name"
                 subheader="For organizations reporting on paper or in person. The entry joins the verification queue like any other.">
    <form method="POST" action="{{ route('admin.organizations.activities.store', $organization) }}" enctype="multipart/form-data"
          class="panel max-w-2xl space-y-6 p-6">
        @csrf

        @include('activities.fields')

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Log on their behalf</button>
            <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-layouts.app>
