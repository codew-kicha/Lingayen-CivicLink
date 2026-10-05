<x-layouts.admin :header="'Encode application: '.$organization->name"
                 subheader="For organizations filing on paper. Scan each requirement and upload it here; the application joins the review queue marked as assisted.">
    <form method="POST" action="{{ route('admin.organizations.applications.store', $organization) }}"
          enctype="multipart/form-data" class="max-w-3xl space-y-6">
        @csrf

        @include('applications.fields')

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Submit on their behalf</button>
            <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
