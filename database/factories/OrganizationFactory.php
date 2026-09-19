<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->company().' Association',
            'sector' => fake()->randomElement(config('sectors')),
            'barangay' => fake()->randomElement(config('barangays')),
            'advocacy' => fake()->paragraph(),
            'org_chart' => "President\nVice President\nSecretary\nTreasurer",
            'public_visibility' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['public_visibility' => false]);
    }
}
