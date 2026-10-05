<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\AnnualReport;
use App\Models\AuditLog;
use App\Models\ApplicationModel;
use App\Models\NewsPost;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentPrecheck;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Rebuilds a realistic demo dataset in one command:
 *   php artisan migrate:fresh --seed
 *
 * Covers organizations in every accreditation state, applications at every SB reading stage,
 * and a mix of verified, pending, and rejected activities (PRD §8, demo reliability).
 */
class DatabaseSeeder extends Seeder
{
    // Every demo account uses this. It satisfies Password::defaults() like a real password must.
    public const DEMO_PASSWORD = 'Lingayen-Demo-2026';

    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'PESO Administrator',
            'email' => 'admin@lingayen.gov.ph',
            'password' => Hash::make(self::DEMO_PASSWORD),
        ]);

        $this->accreditedOrganizations($admin);
        $this->organizationsInReview();
        $this->rejectedApplicant();
        $this->brandNewRegistrant();
        $this->accountStates($admin);
        $this->newsAndReports($admin);
        $this->computeScores();
    }

    /**
     * Accredited organizations with verified activity histories, one or more per real sector.
     * Activity titles are sector-plausible because they surface verbatim in the home-page ticker.
     */
    private function accreditedOrganizations(User $admin): void
    {
        $profiles = [
            ['Pangapisan Fisherfolk Association', 'Farmers and Fisherfolks', 'Pangapisan North',
                'Protects the shoreline and mangrove areas through regular clean-ups and coastal monitoring.',
                ['coastal clean-up', 'mangrove planting', 'safe fishing orientation']],
            ['Bantayan Women Weavers Cooperative', 'Cooperative', 'Bantayan',
                'Trains members in weaving and handicraft production, and runs a shared marketing programme.',
                ['weaving skills training', 'product pricing workshop', 'members\' general assembly']],
            ['Poblacion TODA', 'TODA', 'Poblacion',
                'Represents tricycle operators and drivers on route, fare, and road safety concerns.',
                ['road safety seminar', 'free rides for senior citizens on market day', 'terminal clean-up']],
            ['Malawa Farmers Association', 'Farmers and Fisherfolks', 'Malawa',
                'Supports smallholder rice and vegetable farmers with seed sharing and training.',
                ['seed sharing day', 'organic fertilizer training', 'community vegetable garden launch']],
            ['Domalandan Senior Citizens Circle', 'Senior Citizen', 'Domalandan Center',
                'Runs wellness sessions and a medicine assistance programme for older residents.',
                ['senior wellness day', 'free blood pressure screening', 'medicine assistance distribution']],
            ['Sabangan Rural Improvement Club', 'Rural Improvement Club', 'Sabangan',
                'Organizes home-based livelihood and nutrition programmes for rural households.',
                ['backyard gardening demonstration', 'food processing training', 'nutrition class for mothers']],
            ['KALIPI Libsong West', "KALIPI (Women's)", 'Libsong West',
                'Brings women together for livelihood, health, and anti-violence advocacy.',
                ['anti-VAWC awareness forum', 'livelihood skills training', 'women\'s health caravan']],
            ['Baay Pedicab Drivers Association', 'Pedicab Drivers', 'Baay',
                'Represents pedicab drivers and runs a mutual aid fund for members.',
                ['road safety orientation', 'mutual aid fund assembly', 'pedicab terminal clean-up']],
            ['Lingayen OFW Families Circle', 'OFW', 'Poblacion',
                'Supports families of overseas Filipino workers with financial literacy and reintegration help.',
                ['financial literacy seminar', 'reintegration counselling session', 'OFW family day']],
        ];

        // One organization is deliberately hidden to demonstrate directory moderation.
        $hiddenIndex = 5;

        foreach ($profiles as $index => [$name, $sector, $barangay, $advocacy, $activityTitles]) {
            $user = User::factory()->create([
                'name' => "Representative, {$name}",
                'email' => 'rep'.($index + 1).'@example.ph',
                'password' => Hash::make(self::DEMO_PASSWORD),
            ]);

            $organization = Organization::factory()->for($user)->create([
                'name' => $name,
                'sector' => $sector,
                'barangay' => $barangay,
                'advocacy' => $advocacy,
                'public_visibility' => $index !== $hiddenIndex,
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

            // TODA groups mostly turn up at LGU events rather than running their own, the pattern the
            // client described; the analytics should make that visible.
            $source = fn () => fake()->boolean($sector === 'TODA' ? 10 : 65) ? 'independent' : 'lgu_organized';

            // Real sentences, not lorem ipsum: these print verbatim on the public profile.
            $describe = fn (string $source) => ($source === 'independent'
                    ? 'Organized by members for residents of Barangay '.$barangay
                    : 'Members took part in the municipal government\'s event in Barangay '.$barangay)
                .'. Attendance was recorded on the sign-in sheet submitted to the Civil Society Desk Office.';

            // The last organization has gone quiet, so the dashboard's inactive flag has a real case.
            $quiet = $index === count($profiles) - 1;

            foreach (range(1, fake()->numberBetween(4, 10)) as $n) {
                // Spread across the year, with at least one recent entry for every active organization.
                $heldOn = now()->subDays(match (true) {
                    $quiet => fake()->numberBetween(200, 300),
                    $n === 1 => fake()->numberBetween(2, 60),
                    default => fake()->numberBetween(2, 340),
                });
                $title = fake()->randomElement($activityTitles);
                $from = $source();

                Activity::factory()->for($organization)->create([
                    'title' => ucfirst($title),
                    'description' => $describe($from),
                    'activity_date' => $heldOn->toDateString(),
                    'activity_source' => $from,
                    'status' => 'verified',
                    // PESO verifies a few days after the activity, never before it.
                    'verified_at' => $heldOn->copy()->addDays(fake()->numberBetween(1, 6))->min(now()),
                    'logged_by' => $user->id,
                    'verified_by' => $admin->id,
                ]);
            }

            foreach (range(1, fake()->numberBetween(1, 3)) as $_) {
                if ($quiet) {
                    break;
                }
                $title = fake()->randomElement($activityTitles);
                $from = $source();

                Activity::factory()->for($organization)->create([
                    'title' => ucfirst($title),
                    'description' => $describe($from),
                    'activity_source' => $from,
                    'activity_date' => now()->subDays(fake()->numberBetween(1, 14))->toDateString(),
                    'logged_by' => $user->id,
                ]);
            }
        }

        // Cross-CSO tags: one verified joint activity, and one still awaiting verification.
        $organizations = Organization::whereIn('name', array_column($profiles, 0))->orderBy('id')->get();
        $joint = $organizations[0]->activities()->where('status', 'verified')->latest('activity_date')->first();
        $joint->update(['title' => 'Joint coastal clean-up', 'activity_source' => 'independent', 'description' => 'Fisherfolk and weavers cleared the Pangapisan shoreline together and handed the waste tally to the environment office.']);
        $joint->partnerOrganizations()->attach([$organizations[1]->id, $organizations[3]->id]);
        $organizations[2]->activities()->where('status', 'pending')->first()?->partnerOrganizations()->attach($organizations[0]->id);
    }

    /** Applications sitting at each Sangguniang Bayan reading stage. */
    private function organizationsInReview(): void
    {
        $stages = [
            ['not_endorsed', 'Tonton Neighborhood Association', 'Independent Organizations', 'Tonton'],
            ['first_reading', 'Estanza Fisherfolk Alliance', 'Farmers and Fisherfolks', 'Estanza'],
            ['second_reading', 'Libsong Health Advocates', 'Health', 'Libsong East'],
            ['third_reading', 'Maniboc TODA', 'TODA', 'Maniboc'],
        ];

        foreach ($stages as $index => [$stage, $name, $sector, $barangay]) {
            $user = User::factory()->create([
                'name' => "Representative, {$name}",
                'email' => 'applicant'.($index + 1).'@example.ph',
                'password' => Hash::make(self::DEMO_PASSWORD),
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

            // The second-reading applicant attached its receipt in the by-laws slot; the pre-check flags it.
            $this->documents($organization, $application, ocr: $stage === 'second_reading' ? [
                'constitution_bylaws' => 'Official Receipt. Office of the Municipal Treasurer. Amount: PHP 1,000.00.',
            ] : []);
        }
    }

    private function rejectedApplicant(): void
    {
        $user = User::factory()->create([
            'name' => 'Representative, Wawa Community Circle',
            'email' => 'rejected@example.ph',
            'password' => Hash::make(self::DEMO_PASSWORD),
        ]);

        $organization = Organization::factory()->for($user)->create([
            'name' => 'Wawa Community Circle',
            'sector' => 'Independent Organizations',
            'barangay' => 'Wawa',
        ]);

        $application = ApplicationModel::factory()->for($organization)->rejected()->create([
            'submitted_by' => $user->id,
        ]);

        $this->documents($organization, $application);
    }

    /** Registered through the public form but has not filed an application yet (empty-state walkthrough). */
    private function brandNewRegistrant(): void
    {
        $user = User::factory()->create([
            'name' => 'Lorna Bautista',
            'email' => 'new@example.ph',
            'password' => Hash::make(self::DEMO_PASSWORD),
        ]);

        Organization::factory()->for($user)->create([
            'name' => 'Quibaol Rural Improvement Club',
            'sector' => 'Rural Improvement Club',
            'barangay' => 'Quibaol',
            'advocacy' => 'Home gardening and food preservation for farming households.',
        ]);
    }

    /** One of each account state the Accounts screen has to show. */
    private function accountStates(User $admin): void
    {
        // Registered by the office from its paper records; nobody can sign in for it yet.
        $paperOnly = Organization::factory()->create([
            'user_id' => null,
            'name' => 'Aliwekwek Farmers Association',
            'sector' => 'Farmers and Fisherfolks',
            'barangay' => 'Aliwekwek',
        ]);

        // Its paper application, encoded by PESO staff (assisted encoding).
        $assisted = ApplicationModel::factory()->for($paperOnly)->create([
            'status' => 'submitted',
            'submission_channel' => 'assisted',
            'submitted_by' => $admin->id,
            'submitted_at' => now()->subDays(3),
        ]);
        // Handwritten paper form, scanned at the office: too little text to read.
        $this->documents($paperOnly, $assisted, ocr: ['accreditation_form' => 'Aliwekwek  ~ 2026']);
        AuditLog::record('application.assisted', $paperOnly, ['application_id' => $assisted->id], $admin);

        // Invited by the office; the representative hasn't set a password yet.
        $invited = User::factory()->unverified()->create([
            'name' => 'Ramon Aquino',
            'email' => 'invited@example.ph',
            'password' => Hash::make(Str::random(64)),
        ]);
        Organization::factory()->for($invited)->create([
            'name' => 'Libsong East OFW Families',
            'sector' => 'OFW',
            'barangay' => 'Libsong East',
        ]);

        $deactivated = User::factory()->create([
            'name' => 'Teresa Lim',
            'email' => 'deactivated@example.ph',
            'password' => Hash::make(self::DEMO_PASSWORD),
        ]);
        $deactivated->forceFill([
            'is_active' => false,
            'deactivated_at' => now()->subWeeks(3),
            'deactivation_reason' => 'Organization dissolved; members joined another association.',
        ])->save();
        Organization::factory()->for($deactivated)->create([
            'name' => 'Tonton Youth Volunteers',
            'sector' => 'Independent Organizations',
            'barangay' => 'Tonton',
        ]);

        AuditLog::record('account.deactivated', $deactivated, ['reason' => $deactivated->deactivation_reason], $admin);
    }

    private function newsAndReports(User $admin): void
    {
        $accredited = Organization::where('public_visibility', true)->take(3)->get();

        $posts = [
            ['Accreditation window for 2026 now open', 'The Civil Society Desk Office is accepting applications for CSO accreditation until the end of the quarter. Organizations may file online or bring two copies of each requirement to #1 Bengson Street for assisted encoding.'],
            ['Coastal clean-up covers four barangays', 'Fisherfolk and farmers\' associations joined the quarterly shoreline clean-up, collecting waste across four coastal barangays and recording the volumes for the environment office.'],
            ['Reminder on document expiry', 'Accredited organizations are reminded to replace expiring DOLE or SEC certifications and updated officer lists before they lapse. The system now sends a reminder ahead of each expiry date.'],
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

    /**
     * Placeholder uploads with realistic OCR pre-check results. $ocr overrides the text "read" from
     * a requirement, to show the reviewer's flags (a wrong file, a handwritten scan).
     */
    private function documents(Organization $organization, ApplicationModel $application, bool $expiring = false, array $ocr = []): void
    {
        $readText = [
            'accreditation_form' => "Application for CSO Accreditation. Name of organization: {$organization->name}. Barangay {$organization->barangay}. Signature of president.",
            'officers_members_list' => 'List of Officers and Members. Position: President, Secretary, Treasurer, Auditor.',
            'constitution_bylaws' => "Constitution and By-Laws of {$organization->name}. Article I. Section 1. Membership.",
            'fee_receipt' => 'Official Receipt. Office of the Municipal Treasurer. Amount: PHP 1,000.00. Payment received.',
            'dole_sec_certification' => 'Certificate of Registration. Department of Labor and Employment.',
        ];

        foreach (array_keys(config('document_types')) as $index => $type) {
            $check = DocumentPrecheck::evaluate($type, array_key_exists($type, $ocr) ? $ocr[$type] : $readText[$type], $organization->name);

            $organization->documents()->create([
                'ocr_status' => $check['status'],
                'ocr_details' => $check['details'],
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
