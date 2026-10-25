<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ListPagination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** The shared pagination helper and <x-pagination> component, on a test-only list of users. */
class PaginationComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/__list', function (Request $request) {
            $users = ListPagination::paginate(User::query()->orderBy('id'), $request, 10);

            return Blade::render('<h1 id="page-title" tabindex="-1">List</h1><x-pagination :paginator="$users" :default="10" />', ['users' => $users]);
        });
    }

    public function test_every_list_shows_ten_rows_whatever_size_is_asked_for(): void
    {
        User::factory()->count(60)->create();

        foreach (['', '?per_page=25', '?per_page=50', '?per_page=abc'] as $query) {
            $this->get('/__list'.$query)->assertOk()->assertSee('Showing 1-10 of 60');
        }
    }

    public function test_links_keep_filters(): void
    {
        User::factory()->count(30)->create();

        $html = $this->get('/__list?q=ana&sort=name')->getContent();
        $this->assertStringContainsString('href="http://localhost/__list?q=ana&amp;sort=name&amp;page=2#page-title"', $html);
        $this->assertStringNotContainsString('per_page', $html);
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        User::factory()->count(25)->create();

        $this->get('/__list?page=9&q=ana')->assertRedirect('http://localhost/__list?q=ana&page=3');
    }

    public function test_controls_are_accessible(): void
    {
        User::factory()->count(200)->create();

        $first = $this->get('/__list')->getContent();
        $this->assertStringContainsString('aria-label="Pagination"', $first);
        $this->assertMatchesRegularExpression('/<span class="page-link page-link-disabled" aria-disabled="true">\s*<svg[^>]*>.*?<\/svg>Previous/s', $first);
        $this->assertStringContainsString('aria-current="page"', $first);
        $this->assertMatchesRegularExpression('/<li class="hidden px-1 text-muted sm:block" aria-hidden="true">…<\/li>/', $first);
        $this->assertStringContainsString('Page 1 of 20', $first);
        $this->assertStringNotContainsString('Rows per page', $first);
        $this->assertStringContainsString('class="pagination-bar"', $first);

        $last = $this->get('/__list?page=20')->getContent();
        $this->assertMatchesRegularExpression('/<span class="page-link page-link-disabled" aria-disabled="true">\s*Next/', $last);
    }

    public function test_empty_states_distinguish_filtered_from_nothing_yet(): void
    {
        $filtered = Blade::render('<x-empty-state filtered clear-url="/admin/accounts" />');
        $this->assertStringContainsString('No results for these filters', $filtered);
        $this->assertStringContainsString('href="/admin/accounts"', $filtered);
        $this->assertStringContainsString('Clear filters', $filtered);

        $empty = Blade::render('<x-empty-state title="No news posts yet." />');
        $this->assertStringContainsString('No news posts yet.', $empty);
        $this->assertStringNotContainsString('Clear filters', $empty);
        $this->assertStringNotContainsString('<img', $empty.$filtered);
    }

    public function test_nothing_renders_for_an_empty_list(): void
    {
        $this->assertStringNotContainsString('Showing', $this->get('/__list')->getContent());
    }
}
