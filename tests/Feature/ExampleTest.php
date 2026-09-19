<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_renders_for_a_guest(): void
    {
        $this->get('/')->assertOk()->assertSee('Lingayen CivicLink');
    }
}
