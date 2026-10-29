<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * An organization whose accreditation lapsed last month, for walking through a renewal
 * (expired@example.ph). Part of the full demo seed; on a database that already holds demo data:
 *   php artisan db:seed --class=ExpiredAccreditationSeeder
 * Skips itself when the account already exists.
 */
class ExpiredAccreditationSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'expired@example.ph')->exists()) {
            return;
        }

        $admin = User::where('role', 'admin')->orderBy('id')->first();

        $user = User::factory()->create([
            'name' => 'Representative, Lasip Coastal Watch',
            'email' => 'expired@example.ph',
            'password' => Hash::make(DatabaseSeeder::password()),
        ]);

        $organization = Organization::factory()->for($user)->create([
            'name' => 'Lasip Coastal Watch',
            'sector' => 'Independent Organizations',
            'barangay' => 'Lasip',
            'advocacy' => 'Volunteers who patrol the Lasip shoreline, report illegal fishing, and run clean-ups after storms.',
        ]);

        $organization->members()->createMany([
            ['name' => fake()->name(), 'position' => 'President', 'contact_number' => '0917'.fake()->numerify('#######')],
            ['name' => fake()->name(), 'position' => 'Secretary', 'contact_number' => '0918'.fake()->numerify('#######')],
        ]);

        // Accredited three years ago; the term ended a month ago and nobody filed a renewal.
        $issuedAt = now()->subYears(3)->subMonth();

        $application = ApplicationModel::factory()->for($organization)->approved()->create([
            'submitted_by' => $user->id,
            'reviewed_by' => $admin?->id,
            'submitted_at' => $issuedAt->copy()->subMonth(),
            'reviewed_at' => $issuedAt,
        ]);

        $accreditation = Accreditation::create([
            'organization_id' => $organization->id,
            'application_id' => $application->id,
            'verification_code' => Accreditation::generateVerificationCode(),
            'status' => 'expired',
            'active_org_marker' => null,
            'issued_at' => $issuedAt->toDateString(),
            'expires_at' => $issuedAt->copy()->addYears(3)->toDateString(),
        ]);

        // A record of work during the term, so the renewal has a history to review.
        foreach ([400, 300, 200, 90] as $daysAgo) {
            $heldOn = now()->subDays($daysAgo);

            Activity::factory()->for($organization)->create([
                'title' => fake()->randomElement(['Shoreline clean-up', 'Coastal patrol briefing', 'Storm debris clearing']),
                'description' => 'Organized by members for residents of Barangay Lasip. Attendance was recorded on the sign-in sheet submitted to the Civil Society Desk Office.',
                'activity_date' => $heldOn->toDateString(),
                'activity_source' => 'independent',
                'status' => 'verified',
                'verified_at' => $heldOn->copy()->addDays(3),
                'logged_by' => $user->id,
                'verified_by' => $admin?->id,
            ]);
        }

        // The same entry the daily expiry check writes: no actor, meaning the system.
        AuditLog::create([
            'actor_id' => null,
            'action' => 'accreditation.expired',
            'subject_type' => 'Organization',
            'subject_id' => $organization->id,
            'meta' => ['verification_code' => $accreditation->verification_code, 'expired_on' => $accreditation->expires_at->toDateString()],
        ]);
    }
}
