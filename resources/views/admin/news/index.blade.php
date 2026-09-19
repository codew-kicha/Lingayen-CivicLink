<x-layouts.admin header="News posts" subheader="Published posts appear on the public Resources page and the home page feed.">
    <x-slot:actions>
        <a href="{{ route('admin.news.create') }}" class="btn-primary">Write a post</a>
    </x-slot:actions>

    <section class="panel-flat overflow-hidden">
        @if ($posts->isEmpty())
            <div class="px-4 py-14 text-center">
                <p class="font-medium text-ink">No posts yet.</p>
                <p class="mt-1 text-sm text-muted">Write a post to share an update on the public site.</p>
            </div>
        @else
            <table class="w-full">
                <caption class="sr-only">News posts</caption>
                <thead class="sticky top-0 bg-paper">
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
                                   class="font-medium text-navy-700 hover:underline">{{ $post->title }}</a>
                            </td>
                            <td class="table-cell"><x-status-badge :status="$post->status" /></td>
                            <td class="table-cell text-muted">
                                {{ $post->organizations->pluck('name')->join(', ') ?: 'No organizations tagged' }}
                            </td>
                            <td class="table-cell font-mono tabular-nums text-muted">
                                {{ $post->published_at?->format('d M Y') ?? '--' }}
                            </td>
                            <td class="table-cell text-right">
                                <form method="POST" action="{{ route('admin.news.destroy', $post) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost px-3 py-1.5 text-danger-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <div class="mt-4">{{ $posts->links() }}</div>
</x-layouts.admin>
