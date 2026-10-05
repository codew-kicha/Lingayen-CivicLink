<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Coastal clean-up drive', 'Free medical and dental mission', 'Livelihood skills training',
            'Feeding program for day care children', 'Tree planting along the river easement',
            'Disaster preparedness orientation', 'Senior citizens wellness day',
            'School supplies distribution', 'Community vegetable garden launch',
            'Youth leadership seminar',
        ];

        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->randomElement($titles),
            'description' => fake()->paragraph(),
            'activity_date' => fake()->dateTimeBetween('-10 months', 'now')->format('Y-m-d'),
            'participants_estimate' => fake()->numberBetween(20, 400),
            'activity_source' => fake()->randomElement(array_keys(Activity::SOURCES)),
            'status' => 'pending',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => 'verified',
            'verified_at' => now()->subDays(fake()->numberBetween(1, 20)),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'verified_at' => now()->subDays(fake()->numberBetween(1, 20)),
            'rejection_reason' => 'No supporting photographs or attendance sheet were provided.',
        ]);
    }
}
