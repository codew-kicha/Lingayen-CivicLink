{{--
    Withdraw confirmation (560px), shared by the activity list and the edit page.
    Buttons open it with $dispatch('withdraw-activity', { title, url }).
--}}
<div x-data="{ target: { title: '', url: '' } }" @withdraw-activity.window="target = $event.detail; $dispatch('open-dialog', 'withdraw-activity')">
    <x-dialog name="withdraw-activity" title="Withdraw this activity?" width="sm">
        <form method="POST" :action="target.url" class="flex min-h-0 flex-1 flex-col">
            @csrf
            @method('DELETE')
            <div class="dialog-body">
                <p class="text-sm text-ink">
                    “<span class="font-semibold" x-text="target.title"></span>” will be removed from PESO's verification queue,
                    together with its sign-in sheet and partner tags. This cannot be undone; log it again if you need to.
                </p>
            </div>
            <div class="dialog-footer">
                <button type="button" class="btn-secondary" @click="close()">Keep it</button>
                <button type="submit" class="btn-danger"><x-phosphor-trash class="h-4 w-4" aria-hidden="true" />Withdraw activity</button>
            </div>
        </form>
    </x-dialog>
</div>
