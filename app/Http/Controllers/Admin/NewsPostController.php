<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsPostRequest;
use App\Models\NewsPost;
use App\Models\Organization;
use App\Support\ListPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsPostController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', NewsPost::class);

        return view('admin.news.index', [
            'posts' => ListPagination::paginate(NewsPost::with('organizations:id,name')
                ->when(in_array($request->input('status'), ['draft', 'published'], true), fn ($q) => $q->where('status', $request->input('status')))
                ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
                ->latest('id'),
                $request),
            'organizations' => $this->organizations(),
        ]);
    }

    // Posts are written and edited in the "Write a post" dialog on the list; these addresses open it.
    public function create(): RedirectResponse
    {
        $this->authorize('create', NewsPost::class);

        return redirect()->route('admin.news.index', ['write' => 1]);
    }

    public function store(StoreNewsPostRequest $request): RedirectResponse
    {
        $post = NewsPost::create([
            ...$request->safe()->only(['title', 'body', 'status']),
            'author_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($request->validated('title')),
            'published_at' => $request->validated('status') === 'published' ? now() : null,
        ]);

        $post->organizations()->sync($request->validated('organizations', []));

        return redirect()
            ->route('admin.news.index')
            ->with('status', $post->status === 'published' ? 'Post published.' : 'Draft saved.');
    }

    public function edit(NewsPost $newsPost): RedirectResponse
    {
        $this->authorize('update', $newsPost);

        return redirect()->route('admin.news.index', ['edit' => $newsPost->id]);
    }

    public function update(StoreNewsPostRequest $request, NewsPost $newsPost): RedirectResponse
    {
        $status = $request->validated('status');

        $newsPost->update([
            ...$request->safe()->only(['title', 'body', 'status']),
            // Keep the original publication date when re-editing an already published post.
            'published_at' => $status === 'published' ? ($newsPost->published_at ?? now()) : null,
        ]);

        $newsPost->organizations()->sync($request->validated('organizations', []));

        return redirect()
            ->route('admin.news.index')
            ->with('status', $status === 'published' ? 'Post published.' : 'Draft saved.');
    }

    public function destroy(NewsPost $newsPost): RedirectResponse
    {
        $this->authorize('delete', $newsPost);

        $newsPost->delete();

        return redirect()->route('admin.news.index')->with('status', 'Post deleted.');
    }

    /** Choices for the Featured organizations picker. */
    private function organizations(): array
    {
        return Organization::orderBy('name')->get(['id', 'name'])->toArray();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (NewsPost::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
