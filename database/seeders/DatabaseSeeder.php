<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\AnnualReport;
use App\Models\ApplicationModel;
use App\Models\NewsPost;
use App\Models\Organization;
use App\Models\User;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Rebuilds a realistic demo dataset in one command:
 *   php artisan migrate:fresh --seed
 *
 * Covers organizations in every accreditation state, applications at every SB reading stage,
 * and a mix of verified, pending, and rejected activities (PRD §8, demo reliability).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'PESO Administrator',
            'email' => 'admin@lingayen.gov.ph',
            'password' => Hash::make('password'),
        ]);

        $this->accreditedOrganizations($admin);
        $this->organizationsInReview();
        $this->rejectedApplicant();
        $this->brandNewRegistrant();
        $this->newsAndReports($admin);
        $this->computeScores();
    }

    /** Six accredited organizations with verified activity histories. */
    private function accreditedOrganizations(User $admin): void
    {
        $profiles = [
            ['Lingayen Coastal Watch', 'Environment', 'Pangapisan North', 'Protects the shoreline and mangrove areas through regular clean-ups and coastal monitoring.'],
            ['Bantayan Women Weavers Cooperative', "Women's Welfare", 'Bantayan', 'Trains women in weaving and handicraft production, and runs a shared marketing programme.'],
            ['Poblacion Youth Movement', 'Youth and Sports', 'Poblacion', 'Organizes leadership training, sports leagues, and out-of-school youth tutoring.'],
            ['Malawa Farmers Association', 'Agriculture and Fisheries', 'Malawa', 'Supports smallholder rice and vegetable farmers with seed sharing and training.'],
            ['Domalandan Senior Citizens Circle', 'Senior Citizens', 'Domalandan Center', 'Runs wellness sessions and a medicine assistance programme for older residents.'],
            ['Sabangan Disaster Response Volunteers', 'Disaster Risk Reduction', 'Sabangan', 'Trains barangay volunteers in first aid, evacuation, and flood early warning.'],
        ];

        foreach ($profiles as $index => [$name, $sector, $barangay, $advocacy]) {
            $user = User::factory()->create([
                'name' => "Representative, {$name}",
                'email' => 'rep'.($index + 1).'@example.ph',
                'password' => Hash::make('password'),
            ]);

            $organization = Organization::factory()->for($user)->create([
                'name' => $name,
                'sector' => $sector,
                'barangay' => $barangay,
                'advocacy' => $advocacy,
                // One organization is deliberately hidden to demonstrate directory moderation.
                'public_visibility' => $index !== 5,
            ]);

            $organization->members()->createMany([
                ['name' => fake()->name(), 'position' => 'President', 'contact_number' => '0917'.fake()->numerify('#######')],
                ['name' => fake()->name(), 'position' => 'Secretary', 'contact_number' => '0918'.fake()->numerify('#######')],
                ['name' => fake()->name(), 'position' => 'Treasurer', 'contact_number' => '0919'.fake()->numerify('#######')],
            ]);

            $application = ApplicationModel::factory()->for($organization)->approved()->create([
                'submitted_by' => $user->id,
                'reviewed_by' => $admin->id,
                'submitted_at' => now()->subMonths(10),
            ]);

            $issuedAt = now()->subMonths(9);

            Accreditation::create([
                'organization_id' => $organization->id,
                'application_id' => $application->id,
                'verification_code' => Accreditation::generateVerificationCode(),
                'status' => 'active',
                'active_org_marker' => $organization->id,
                'issued_at' => $issuedAt->toDateString(),
                // The last one expires soon, to exercise the renewal reminder path.
                'expires_at' => $index === 4
                    ? now()->addDays(45)->toDateString()
                    : $issuedAt->copy()->addYears(3)->toDateString(),
            ]);

            $this->documents($organization, $application, expiring: $index === 3);

            Activity::factory()
                ->count(fake()->numberBetween(3, 9))
                ->for($organization)
                ->verified()
                ->create(['logged_by' => $user->id, 'verified_by' => $admin->id]);

            Activity::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($organization)
                ->create(['logged_by' => $user->id]);
        }
    }

    /** Applications sitting at each Sangguniang Bayan reading stage. */
    private function organizationsInReview(): void
    {
        $stages = [
            ['not_endorsed', 'Tonton Neighborhood Association', 'General / Multi-Sectoral', 'Tonton'],
            ['first_reading', 'Estanza Fisherfolk Alliance', 'Agriculture and Fisheries', 'Estanza'],
            ['second_reading', 'Libsong Health Advocates', 'Health', 'Libsong East'],
            ['third_reading', 'Maniboc Education Support Group', 'Education', 'Maniboc'],
        ];

        foreach ($stages as $index => [$stage, $name, $sector, $barangay]) {
            $user = User::factory()->create([
                'name' => "Representative, {$name}",
                'email' => 'applicant'.($index + 1).'@example.ph',
                'password' => Hash::make('password'),
            ]);

            $organization = Organization::factory()->for($user)->create([
                'name' => $name,
                'sector' => $sector,
                'barangay' => $barangay,
            ]);

            $organization->members()->createMany([
                ['name' => fake()->name(), 'position' => 'President'],
                ['name' => fake()->name(), 'position' => 'Secretary'],
            ]);

            $application = ApplicationModel::factory()
                ->for($organization)
                ->atStage($stage)
                ->create([
                    'status' => $stage === 'not_endorsed' ? 'submitted' : 'under_review',
                    'submitted_by' => $user->id,
                    'submitted_at' => now()->subDays(($index + 1) * 9),
                ]);

            $this->documents($organization, $application);
        }
    }

    private function rejectedApplicant(): void
    {
        $user = User::factory()->create([
            'name' => 'Representative, Wawa Community Circle',
            'email' => 'rejected@example.ph',
            'password' => Hash::make('password'),
        ]);

        $organization = Organization::factory()->for($user)->create([
            'name' => 'Wawa Community Circle',
            'sector' => 'General / Multi-Sectoral',
            'barangay' => 'Wawa',
        ]);

        $application = ApplicationModel::factory()->for($organization)->rejected()->create([
            'submitted_by' => $user->id,
        ]);

        $this->documents($organization, $application);
    }

    /** A rep who has registered but not yet built a profile, for the empty-state walkthrough. */
    private function brandNewRegistrant(): void
    {
        User::factory()->create([
            'name' => 'New Representative',
            'email' => 'new@example.ph',
            'password' => Hash::make('password'),
        ]);
    }

    private function newsAndReports(User $admin): void
    {
        $accredited = Organization::where('public_visibility', true)->take(3)->get();

        $posts = [
            ['Accreditation window for 2026 now open', 'The Public Employment Service Office is accepting applications for CSO accreditation until the end of the quarter. Organizations may file online or bring printed requirements to the municipal hall for assisted encoding.'],
            ['Coastal clean-up covers four barangays', 'Volunteers from several accredited organizations joined the quarterly shoreline clean-up, collecting waste across four coastal barangays and recording the volumes for the environment office.'],
            ['Reminder on document expiry', 'Accredited organizations are reminded to replace expiring registration certificates and financial statements before they lapse. The system now sends a reminder ahead of each expiry date.'],
        ];

        foreach ($posts as $index => [$title, $body]) {
            $post = NewsPost::factory()->create([
                'author_id' => $admin->id,
                'title' => $title,
                'slug' => \Illuminate\Support\Str::slug($title),
                'body' => $body,
                'published_at' => now()->subDays(($index + 1) * 12),
            ]);

            if ($accredited->isNotEmpty()) {
                $post->organizations()->attach($accredited->random(min(2, $accredited->count()))->pluck('id'));
            }
        }

        NewsPost::factory()->draft()->create([
            'author_id' => $admin->id,
            'title' => 'Draft: Local Special Body seat allocations',
            'slug' => 'draft-local-special-body-seat-allocations',
            'body' => 'Pending confirmation from the Sangguniang Bayan Secretariat.',
        ]);

        foreach ([now()->year - 1, now()->year - 2] as $year) {
            AnnualReport::create([
                'title' => "CSO Accreditation and Activity Report {$year}",
                'year' => $year,
                // Placeholder path: the file itself is uploaded through the admin CMS.
                'file_path' => "annual-reports/placeholder-{$year}.pdf",
                'uploaded_by' => $admin->id,
            ]);
        }
    }

    private function documents(Organization $organization, ApplicationModel $application, bool $expiring = false): void
    {
        foreach (array_keys(config('document_types')) as $index => $type) {
            $organization->documents()->create([
                'application_id' => $application->id,
                'document_type' => $type,
                'file_path' => "documents/{$organization->id}/placeholder-{$type}.pdf",
                'original_filename' => "{$type}.pdf",
                'mime_type' => 'application/pdf',
                'expires_at' => match (true) {
                    $expiring && $index === 0 => now()->addDays(21)->toDateString(),
                    $index % 3 === 0 => now()->addYears(2)->toDateString(),
                    default => null,
                },
            ]);
        }
    }

    private function computeScores(): void
    {
        $calculator = app(PerformanceScoreCalculator::class);

        Organization::each(fn (Organization $organization) => $calculator->recalculate($organization));
    }
}
