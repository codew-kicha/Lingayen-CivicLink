<?php

namespace Database\Factories;

use App\Models\ApplicationModel;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationModel>
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
            'submitted_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }

    /** Filed within a free filing period (or waived by the office): no receipt, three-year accreditation. */
    public function feeWaived(): static
    {
        return $this->state(fn () => ['fee_waived' => true]);
    }

    /** Validated and forwarded to the committee, not heard yet. */
    public function forHearing(): static
    {
        return $this->state(fn () => ['status' => 'under_review', 'hearing_outcome' => null]);
    }

    /** Heard by the committee with this outcome (favorable, deferred, returned). */
    public function heard(string $outcome = 'favorable'): static
    {
        return $this->state(fn () => ['status' => 'under_review', 'hearing_outcome' => $outcome]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'hearing_outcome' => 'favorable',
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
