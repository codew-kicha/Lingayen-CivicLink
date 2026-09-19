<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ApplicationModel>
 */
class ApplicationModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'type' => 'new',
            'status' => 'submitted',
            'submission_channel' => 'online',
            'sb_stage' => 'not_endorsed',
            'submitted_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }

    public function atStage(string $stage): static
    {
        return $this->state(fn () => ['sb_stage' => $stage, 'status' => 'under_review']);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'sb_stage' => 'endorsed',
            'reviewed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'rejection_reason' => 'The list of officers was not signed by the current president.',
            'reviewed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
