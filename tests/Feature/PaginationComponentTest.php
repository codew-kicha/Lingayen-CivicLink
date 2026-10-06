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

    public function test_per_page_is_whitelisted_and_falls_back_to_the_default(): void
    {
        User::factory()->count(60)->create();

        foreach (['10' => 10, '25' => 25, '50' => 50, '7' => 10, '1000' => 10, 'abc' => 10, '-5' => 10] as $requested => $expected) {
            $this->get('/__list?per_page='.$requested)->assertOk()
                ->assertSee('Showing 1-'.$expected.' of 60');
        }
    }

    public function test_links_keep_filters_and_omit_a_default_page_size(): void
    {
        User::factory()->count(30)->create();

        $default = $this->get('/__list?q=ana&sort=name')->getContent();
        $this->assertStringContainsString('href="http://localhost/__list?q=ana&amp;sort=name&amp;page=2#page-title"', $default);
        $this->assertStringNotContainsString('per_page=10', $default);

        $custom = $this->get('/__list?q=ana&per_page=25')->getContent();
        $this->assertStringContainsString('q=ana&amp;per_page=25&amp;page=2#page-title', $custom);
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        User::factory()->count(25)->create();

        $this->get('/__list?page=9&q=ana')->assertRedirect('http://localhost/__list?q=ana&page=3');
        $this->get('/__list?page=9&per_page=50')->assertRedirect('http://localhost/__list?per_page=50&page=1');
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
        $this->assertMatchesRegularExpression('/<label for="per-page-\w+"[^>]*>Rows per page<\/label>/', $first);

        $last = $this->get('/__list?page=20')->getContent();
        $this->assertMatchesRegularExpression('/<span class="page-link page-link-disabled" aria-disabled="true">\s*Next/', $last);
    }

    public function test_the_page_size_form_keeps_filters_and_resets_to_page_one(): void
    {
        User::factory()->count(30)->create();

        $html = $this->get('/__list?q=ana&status=active&page=2')->getContent();
        preg_match('/<form method="GET".*?<\/form>/s', $html, $form);

        $this->assertStringContainsString('name="q" value="ana"', $form[0]);
        $this->assertStringContainsString('name="status" value="active"', $form[0]);
        $this->assertStringNotContainsString('name="page"', $form[0]);
        $this->assertStringContainsString('<noscript>', $form[0]);
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
