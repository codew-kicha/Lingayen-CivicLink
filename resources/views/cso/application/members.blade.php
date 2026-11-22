<x-application-frame :draft="$draft" :step="$step" :done="$done">
    <form method="POST" action="{{ route('cso.application.members') }}" class="card">
        @csrf
        <input type="hidden" name="step" value="members">

        <div class="border-b border-line px-6 py-4">
            <h2 class="card-title">Officers and members</h2>
            <p class="mt-1 max-w-[72ch] text-sm text-muted">
                List at least five people. You are first, as the representative; give everyone else their position if
                they have one. Type them in, or fill the Excel template and upload it. This list becomes your "Updated List of
                Officers and Members" and is visible to the Desk Office only.
            </p>
        </div>

        <div class="p-6">
            <x-member-table :organization="$organization" :user="auth()->user()" />
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-line px-6 py-4">
            <button type="submit" name="action" value="later" class="btn-ghost" formnovalidate>Save and finish later</button>
            <button type="submit" name="action" value="next" class="btn-primary ml-auto">
                Save and continue <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </form>
</x-application-frame>
