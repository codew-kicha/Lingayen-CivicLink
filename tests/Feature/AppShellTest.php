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

    public function test_both_roles_share_one_layout_and_differ_only_in_navigation(): void
    {
        $admin = User::factory()->admin()->create();
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create(['name' => 'Pangapisan Fisherfolk Association']);

        $adminPage = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $repPage = $this->actingAs($rep)->get(route('cso.dashboard'))->assertOk();

        foreach ([$adminPage, $repPage] as $page) {
            $page->assertSee('aria-label="Main"', false)->assertSee('Open navigation')->assertSee('Account settings');
        }
        $adminPage->assertSee('Verify activities')->assertSee('Audit log')->assertDontSee('Organization profile');
        $repPage->assertSee('Organization profile')->assertDontSee('Verify activities')->assertDontSee('Audit log');
    }

    public function test_every_page_marks_its_navigation_item_as_current(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['admin.applications.index' => 'Applications', 'admin.activities.index' => 'Verify activities', 'profile.edit' => 'Account settings'] as $route => $label) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();
            $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*(<svg.*?<\/svg>\s*)?(<span[^>]*>)?\s*'.preg_quote($label, '/').'/s', $html, "$label not marked current on $route");
        }
    }

    public function test_position_is_optional_saved_and_shown_under_the_name(): void
    {
        $rep = User::factory()->create(['name' => 'Ana Reyes']);
        Organization::factory()->for($rep)->create(['name' => 'Pangapisan Fisherfolk Association']);

        $this->actingAs($rep)->patch(route('profile.update'), ['name' => 'Ana Reyes', 'email' => $rep->email, 'phone' => '0917 123 4567'])
            ->assertSessionHasNoErrors();
        $this->assertNull($rep->fresh()->position);

        $this->actingAs($rep)->patch(route('profile.update'), ['name' => 'Ana Reyes', 'position' => 'President', 'email' => $rep->email, 'phone' => '0917 123 4567']);
        $this->assertSame('President', $rep->fresh()->position);

        $this->actingAs($rep)->get(route('cso.dashboard'))
            ->assertSee('President, Pangapisan Fisherfolk Association')
            ->assertSee('title="Ana Reyes · President, Pangapisan Fisherfolk Association"', false);
    }
}
