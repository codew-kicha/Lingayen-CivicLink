<x-layouts.cso header="Apply for accreditation"
               subheader="Upload each requirement as a PDF, JPG, or PNG file up to 5 MB.">
    <form method="POST" action="{{ route('cso.applications.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('applications.fields')

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Submit application</button>
            <a href="{{ route('cso.dashboard') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-layouts.cso>
