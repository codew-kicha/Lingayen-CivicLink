<?php

namespace Tests\Feature;

use App\Models\NewsPost;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The signed-in app shell: navigation reachable at every width, and list state kept across pages. */
class AppShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_navigation_is_reachable_below_the_desktop_breakpoint(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Open navigation')
            ->assertSee('aria-modal="true"', false)
            ->assertSee('x-trap.noscroll.inert="drawer"', false);
    }

    public function test_scorecard_and_news_pagination_keep_the_query_string(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->count(26)->create();
        NewsPost::factory()->count(21)->create(['author_id' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.scorecards.index', ['q' => 'kept']))
            ->assertOk()->assertSee('q=kept&amp;page=2', false);
        $this->actingAs($admin)->get(route('admin.news.index', ['status' => 'kept']))
            ->assertOk()->assertSee('status=kept&amp;page=2', false);
    }
}
