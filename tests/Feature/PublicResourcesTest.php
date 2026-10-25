<?php

namespace Tests\Feature;

use App\Models\NewsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_shows_five_a_page_with_the_public_pagination_and_faqs_open_on_click(): void
    {
        foreach (range(1, 15) as $i) {
            NewsPost::factory()->create(['title' => sprintf('Update %02d', $i), 'published_at' => now()->subDays($i)]);
        }

        $page = $this->get(route('resources'))->assertOk();
        $this->assertSame(5, preg_match_all('/>Update \d\d</', $page->getContent()));
        $page->assertSee('Update 01')->assertDontSee('Update 06')
            ->assertSee('aria-label="Pagination"', false)
            ->assertSee('resources?page=2#news', false)
            ->assertSee('<details class="group">', false)
            ->assertSee(config('office.faqs.0.question'));

        $this->get(route('resources', ['page' => 3]))->assertSee('Update 11')->assertSee('Update 15')->assertDontSee('Update 10');
    }
}
