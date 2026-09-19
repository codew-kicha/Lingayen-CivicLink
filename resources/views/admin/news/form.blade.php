@php $editing = $post->exists; @endphp

<x-layouts.admin :header="$editing ? 'Edit post' : 'Write a post'"
                 subheader="Tag the organizations a post is about so it appears on their listings too.">
    <form method="POST"
          action="{{ $editing ? route('admin.news.update', $post) : route('admin.news.store') }}"
          class="max-w-3xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="panel-flat p-5">
            <div class="space-y-5">
                <div>
                    <label for="title" class="field-label">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}"
                           class="field-input @error('title') field-input-error @enderror"
                           @error('title') aria-invalid="true" aria-describedby="title-error" @enderror required>
                    @error('title')<p id="title-error" class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="body" class="field-label">Post</label>
                    <textarea id="body" name="body" rows="12"
                              class="field-input @error('body') field-input-error @enderror"
                              @error('body') aria-invalid="true" aria-describedby="body-error" @enderror required>{{ old('body', $post->body) }}</textarea>
                    @error('body')<p id="body-error" class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Featured organizations</h2>
            <p class="mt-0.5 text-sm text-muted">Optional. Tag any organizations this post is about.</p>

            @php $selected = old('organizations', $post->organizations->pluck('id')->all()); @endphp

            <div class="mt-4 grid max-h-64 gap-1 overflow-y-auto sm:grid-cols-2">
                @foreach ($organizations as $organization)
                    <label class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-navy-50">
                        <input type="checkbox" name="organizations[]" value="{{ $organization->id }}"
                               @checked(in_array($organization->id, $selected))>
                        <span class="text-ink">{{ $organization->name }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="panel-flat p-5">
            <fieldset>
                <legend class="font-semibold text-ink">Visibility</legend>
                <div class="mt-3 space-y-2">
                    @foreach ([
                        'draft' => 'Save as draft, not visible on the public site',
                        'published' => 'Publish now, visible on the Resources page',
                    ] as $value => $label)
                        <label class="flex items-start gap-3 rounded-sm border border-line-strong p-3
                                      hover:border-navy-300 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50">
                            <input type="radio" name="status" value="{{ $value }}" class="mt-0.5"
                                   @checked(old('status', $post->status) === $value) required>
                            <span class="text-sm text-ink">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('status')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>
        </section>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">{{ $editing ? 'Save changes' : 'Save post' }}</button>
            <a href="{{ route('admin.news.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
