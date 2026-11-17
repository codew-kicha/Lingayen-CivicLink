<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pre-final defense panel: the hero plays the new video (smaller, flipping seal) the right way. */
class HeroVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hero_plays_the_new_video_muted_inline_and_looping_with_its_poster(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match('/<video[^>]*>/', $html, $video);
        $this->assertNotEmpty($video);
        foreach (['muted', 'loop', 'playsinline', 'x-ref="video"'] as $attribute) {
            $this->assertStringContainsString($attribute, $video[0]);
        }

        // Versioned by the file's date, so a replaced video or poster is never served from cache.
        $this->assertStringContainsString('videos/lingayen_hero.mp4?v='.filemtime(public_path('videos/lingayen_hero.mp4')), $html);
        $this->assertStringContainsString('videos/lingayen_hero-poster.jpg?v='.filemtime(public_path('videos/lingayen_hero-poster.jpg')), $html);
    }

    public function test_the_video_stays_light_enough_for_phones(): void
    {
        $this->assertLessThan(6 * 1024 * 1024, filesize(public_path('videos/lingayen_hero.mp4')));
        $this->assertLessThan(400 * 1024, filesize(public_path('videos/lingayen_hero-poster.jpg')));
    }
}
