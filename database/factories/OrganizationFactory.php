<?php

namespace Database\Factories;

use App\Models\Accreditation;
use App\Models\ApplicationModel;
use App\Models\Organization;
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

    /** With an approved application and an active accreditation (needed to log activities). */
    public function accredited(): static
    {
        return $this->afterCreating(function (Organization $organization) {
            $application = ApplicationModel::factory()->for($organization)->approved()->create();
            $organization->accreditations()->create([
                'application_id' => $application->id,
                'verification_code' => Accreditation::generateVerificationCode(),
                'status' => 'active',
                'active_org_marker' => $organization->id,
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYears(3)->toDateString(),
            ]);
        });
    }
}
