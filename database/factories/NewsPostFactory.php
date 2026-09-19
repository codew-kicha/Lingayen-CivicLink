<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\NewsPost>
 */
class NewsPostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'author_id' => User::factory()->admin(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'body' => fake()->paragraphs(3, true),
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }
}
