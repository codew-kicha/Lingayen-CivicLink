@php
    $filtered = request()->filled('status') || request()->filled('q');
    $toItem = fn ($post) => [
        'id' => $post->id,
        'title' => $post->title,
        'body' => $post->body,
        'status' => $post->status,
        'organizations' => $post->organizations->pluck('id')->all(),
        'url' => route('admin.news.update', $post),
    ];
    $items = $posts->getCollection()->values()->map($toItem);

    // Reopen the dialog after a validation error, or when arriving from the old create/edit addresses.
    $reopen = $errors->any() && old('_dialog') === 'post';
    $initial = match (true) {
        $reopen => [
            'id' => old('_post') ? (int) old('_post') : null,
            'title' => old('title'),
            'body' => old('body'),
            'status' => old('_status', 'draft'),
            'organizations' => array_map('intval', old('organizations', [])),
            'url' => old('_post') ? route('admin.news.update', (int) old('_post')) : null,
        ],
        request()->filled('edit') && ($editing = \App\Models\NewsPost::with('organizations:id')->find(request()->integer('edit'))) !== null => $toItem($editing),
        default => null,
    };
    $field = fn (string $name) => $reopen && $errors->has($name);
@endphp

<x-layouts.app header="News posts" subheader="Published posts appear on the public Resources page and the home page feed.">
    <div x-data="postEditor(@js($items), @js($initial))" @row-open="edit($event.detail.index)">
        <x-filter-bar search="Search post titles" :chips="['' => 'All posts', 'published' => 'Published', 'draft' => 'Draft']">
            <x-slot:actions>
                <a href="{{ route('admin.news.create') }}" class="btn-primary" @click.prevent="write()">
                    <x-phosphor-pencil-simple class="h-4 w-4" aria-hidden="true" />Write a post
                </a>
            </x-slot:actions>
        </x-filter-bar>

        <section class="card">
            @if ($posts->isEmpty())
                <x-empty-state :filtered="$filtered" :clear-url="route('admin.news.index')" title="No posts yet." icon="newspaper">
                    Write a post to share an update on the public site.
                </x-empty-state>
            @else
                <div class="table-scroll">
                    <table class="w-full">
                        <caption class="sr-only">News posts. Use the arrow keys to move between rows and Enter to edit one.</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head">Title</th>
                                <th scope="col" class="table-head w-32">Status</th>
                                <th scope="col" class="table-head">Featuring</th>
                                <th scope="col" class="table-head w-32">Published</th>
                                <th scope="col" class="table-head w-28"><span class="sr-only">Delete</span></th>
                            </tr>
                        </thead>
                        <tbody x-data="tableNav" @click="onClick" @keydown="onKeydown">
                            @foreach ($posts as $post)
                                <tr class="table-row-link" data-row data-index="{{ $loop->index }}" tabindex="{{ $loop->first ? 0 : -1 }}" aria-haspopup="dialog">
                                    <td class="table-cell min-w-64 max-w-md">
                                        <p class="font-semibold text-ink">{{ $post->title }}</p>
                                    </td>
                                    <td class="table-cell"><x-status-badge :status="$post->status" /></td>
                                    <td class="table-cell min-w-56 text-muted">
                                        {{ $post->organizations->pluck('name')->join(', ') ?: 'No organizations tagged' }}
                                    </td>
                                    <td class="table-date">{{ $post->published_at?->format('d M Y') ?? '--' }}</td>
                                    <td class="table-cell text-right">
                                        <button type="button" class="btn-danger btn-sm"
                                                @click="$dispatch('delete-post', @js(['title' => $post->title, 'url' => route('admin.news.destroy', $post)]))">
                                            <x-phosphor-trash class="h-4 w-4" aria-hidden="true" />Delete<span class="sr-only"> “{{ $post->title }}”</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <x-pagination :paginator="$posts" />
        </section>

        {{-- Write / edit (720px). Publish and Save as draft are both submit buttons that set the status. --}}
        <x-dialog name="post" width="lg" :open="$initial !== null || request()->boolean('write')">
            <x-slot:header>
                <div class="min-w-0">
                    <h2 id="dialog-post-title" class="card-title" x-text="post.id ? 'Edit post' : 'Write a post'">Write a post</h2>
                    <p class="mt-1 text-sm text-muted">Tag the organizations a post is about so it appears on their listings too.</p>
                </div>
            </x-slot:header>

            <form method="POST" :action="post.id ? post.url : @js(route('admin.news.store'))" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="! post.id">
                <input type="hidden" name="_dialog" value="post">
                <input type="hidden" name="_post" :value="post.id">
                <input type="hidden" name="_status" :value="post.status">

                <div class="dialog-body space-y-6">
                    <p class="text-sm text-muted"><span class="text-danger" aria-hidden="true">*</span> Required</p>

                    <div>
                        <label for="post-title" class="field-label field-required">Title</label>
                        <input id="post-title" name="title" x-model="post.title" autofocus required maxlength="255"
                               placeholder="e.g. 12 organizations accredited this quarter"
                               @class(['field-input', 'field-input-error' => $field('title')])
                               @if ($field('title')) aria-invalid="true" aria-describedby="post-title-error" @endif>
                        @if ($field('title'))<p id="post-title-error" class="field-error">{{ $errors->first('title') }}</p>@endif
                    </div>

                    <div>
                        <label for="post-body" class="field-label field-required">Post content</label>
                        <textarea id="post-body" name="body" x-model="post.body" rows="8" required maxlength="20000"
                                  placeholder="Write the update as it should appear on the public site"
                                  @class(['field-input', 'field-input-error' => $field('body')])
                                  @if ($field('body')) aria-invalid="true" aria-describedby="post-body-error" @endif></textarea>
                        @if ($field('body'))<p id="post-body-error" class="field-error">{{ $errors->first('body') }}</p>@endif
                    </div>

                    <div x-data="tagPicker(@js($organizations))" x-modelable="selected" x-model="post.organizations"
                         class="relative" @focusout="settle()">
                        <label for="post-orgs" class="field-label">Featured organizations <span class="font-normal text-muted">(optional)</span></label>
                        <div class="flex min-h-11 flex-wrap items-center gap-2 rounded-sm border border-line-strong bg-paper px-2 py-1
                                    focus-within:border-primary hover:border-primary/60">
                            <template x-for="organization in chosen" :key="organization.id">
                                <span class="inline-flex min-h-8 items-center gap-1 rounded-sm bg-primary-subtle pl-2 text-sm text-ink">
                                    <span x-text="organization.name"></span>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-muted hover:text-danger touch:h-11 touch:w-11"
                                            @click="remove(organization.id)">
                                        <x-phosphor-x class="h-4 w-4" aria-hidden="true" />
                                        <span class="sr-only" x-text="'Remove ' + organization.name"></span>
                                    </button>
                                    <input type="hidden" name="organizations[]" :value="organization.id">
                                </span>
                            </template>
                            <input id="post-orgs" x-ref="input" x-model="query" autocomplete="off"
                                   role="combobox" aria-autocomplete="list" aria-controls="post-orgs-list" aria-describedby="post-orgs-hint"
                                   :aria-expanded="expanded.toString()"
                                   :aria-activedescendant="expanded && matches[active] ? 'post-org-' + matches[active].id : null"
                                   @focus="expanded = true" @input="expanded = true; active = 0"
                                   @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter="addActive($event)"
                                   @keydown.backspace="removeLast()"
                                   @keydown.escape="if (expanded) { expanded = false; $event.stopPropagation() }"
                                   :placeholder="chosen.length ? 'Add another' : 'Search organizations'"
                                   class="min-h-9 min-w-40 flex-1 border-0 bg-transparent px-1 text-base text-ink placeholder:text-placeholder focus:ring-0">
                        </div>
                        <ul id="post-orgs-list" role="listbox" aria-label="Organizations" x-show="expanded && matches.length" x-cloak class="listbox">
                            <template x-for="(organization, i) in matches" :key="organization.id">
                                <li role="option" :id="'post-org-' + organization.id" aria-selected="false"
                                    :data-active="i === active ? '' : null" @mousedown.prevent="add(organization)" @mousemove="active = i"
                                    class="listbox-option" x-text="organization.name"></li>
                            </template>
                        </ul>
                        <p id="post-orgs-hint" class="field-hint">The post also appears on these organizations' public listings.</p>
                        @if ($reopen && $errors->hasAny(['organizations', 'organizations.*']))<p class="field-error">{{ $errors->first('organizations') ?: $errors->first('organizations.*') }}</p>@endif
                    </div>
                </div>

                <div class="dialog-footer">
                    <button type="button" class="btn-ghost" @click="close()">Cancel</button>
                    <button type="submit" name="status" value="draft" class="btn-secondary"
                            x-text="post.status === 'published' ? 'Move to drafts' : 'Save as draft'">Save as draft</button>
                    <button type="submit" name="status" value="published" class="btn-primary"
                            x-text="post.id && post.status === 'published' ? 'Update post' : 'Publish'">Publish</button>
                </div>
            </form>
        </x-dialog>

        {{-- Delete confirmation (560px). --}}
        <div x-data="{ target: { title: '', url: '' } }" @delete-post.window="target = $event.detail; $dispatch('open-dialog', 'delete-post')">
            <x-dialog name="delete-post" title="Delete this post?" width="sm">
                <form method="POST" :action="target.url" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    @method('DELETE')
                    <div class="dialog-body">
                        <p class="text-sm text-ink">
                            “<span class="font-semibold" x-text="target.title"></span>” will be removed from the public site and
                            from the listings it features. This cannot be undone.
                        </p>
                    </div>
                    <div class="dialog-footer">
                        <button type="button" class="btn-secondary" @click="close()">Cancel</button>
                        <button type="submit" class="btn-danger"><x-phosphor-trash class="h-4 w-4" aria-hidden="true" />Delete post</button>
                    </div>
                </form>
            </x-dialog>
        </div>
    </div>
</x-layouts.app>
