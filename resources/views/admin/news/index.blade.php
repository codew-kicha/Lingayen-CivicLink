<x-layouts.app header="News posts" subheader="Published posts appear on the public Resources page and the home page feed.">
    <x-slot:actions>
        <a href="{{ route('admin.news.create') }}" class="btn-primary">Write a post</a>
    </x-slot:actions>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-input w-48">
                <option value="">All posts</option>
                <option value="published" @selected(request('status') === 'published')>Published</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
            </select>
        </div>
        <x-keep-query />
        <button type="submit" class="btn-primary">Filter</button>
        @if (request()->filled('status'))
            <a href="{{ route('admin.news.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    <section class="card">
        @if ($posts->isEmpty())
            <x-empty-state :filtered="request()->filled('status')" :clear-url="route('admin.news.index')" title="No posts yet." icon="newspaper">
                Write a post to share an update on the public site.
            </x-empty-state>
        @else
            <div class="table-wrap">
            <table class="w-full">
                <caption class="sr-only">News posts</caption>
                <thead>
                    <tr>
                        <th scope="col" class="table-head">Title</th>
                        <th scope="col" class="table-head">Status</th>
                        <th scope="col" class="table-head">Featuring</th>
                        <th scope="col" class="table-head">Published</th>
                        <th scope="col" class="table-head"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($posts as $post)
                        <tr class="table-row">
                            <td class="table-cell">
                                <a href="{{ route('admin.news.edit', $post) }}"
                                   class="entity-link">{{ $post->title }}</a>
                            </td>
                            <td class="table-cell"><x-status-badge :status="$post->status" /></td>
                            <td class="table-cell text-muted">
                                {{ $post->organizations->pluck('name')->join(', ') ?: 'No organizations tagged' }}
                            </td>
                            <td class="table-date">
                                {{ $post->published_at?->format('d M Y') ?? '--' }}
                            </td>
                            <td class="table-cell text-right">
                                <form method="POST" action="{{ route('admin.news.destroy', $post) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost px-3 py-2 text-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </section>

    <x-pagination :paginator="$posts" :default="10" />
</x-layouts.app>
