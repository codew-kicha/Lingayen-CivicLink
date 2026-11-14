<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\AnnualReport;
use App\Models\AuditLog;
use App\Models\ApplicationModel;
use App\Models\Document;
use App\Models\NewsPost;
use App\Models\OfficeNotice;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OfficeNoticeSent;
use App\Services\ApplicationSteps;
use App\Services\DocumentPrecheck;
use App\Services\PerformanceScoreCalculator;
use App\Services\SheetChecks;
use Illuminate\Support\Facades\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
    // The development password for every demo account. It is in a public repository, so a live
    // server must set its own (DEMO_PASSWORD) before demo data may be seeded there.
    public const DEMO_PASSWORD = 'Lingayen-Demo-2026';

    /** Published demo news, newest first: [title, body]. Fifteen, so the Resources list pages (5 a page). */
    public const NEWS_POSTS = [
        ['Accreditation window for 2026 now open', 'The Civil Society Desk Office is accepting applications for CSO accreditation until the end of the quarter. Organizations may file online or bring two copies of each requirement to #1 Bengson Street for assisted encoding.'],
        ['Coastal clean-up covers four barangays', 'Fisherfolk and farmers\' associations joined the quarterly shoreline clean-up, collecting waste across four coastal barangays and recording the volumes for the environment office.'],
        ['Reminder on document expiry', 'Accredited organizations are reminded to replace expiring DOLE or SEC certifications and updated officer lists before they lapse. The Desk Office emails a reminder ahead of each accreditation\'s expiry date.'],
        ['Senior citizens\' wellness day draws 300 residents', 'Senior citizens\' groups from Domalandan and Talogtog held a joint wellness day with free blood pressure checks, light exercise, and a talk on managing maintenance medicines. Barangay health workers assisted throughout the morning.'],
        ['Online filing now available for CSO accreditation', 'Organizations can now register, upload their requirements, and follow their application through each Sangguniang Bayan reading on the CSO Desk Office website. Paper filing at the office remains available for groups without internet access.'],
        ['Orientation on the accreditation requirements', 'The Desk Office will hold an orientation on the five accreditation requirements for new and renewing organizations. Officers are encouraged to bring their draft documents so staff can check them on the spot.'],
        ['Mangrove planting along the Pangapisan coast', 'Fisherfolk and youth volunteers planted 1,200 mangrove propagules along the Pangapisan shoreline. The planting site will be monitored monthly, and survival counts will be shared with the municipal environment office.'],
        ['Women\'s organizations lead anti-VAWC forum', 'KALIPI chapters and the Capandanan Kababaihan Association held a forum on preventing violence against women and children, with resource speakers from the municipal social welfare office and the women and children protection desk.'],
        ['Road safety seminar for tricycle and pedicab drivers', 'TODA and pedicab drivers\' associations attended a road safety seminar on traffic rules, passenger safety, and first aid. Drivers who completed the seminar received certificates from the municipal traffic office.'],
        ['Cooperatives attend bookkeeping workshop', 'Officers of six cooperatives completed a two-day bookkeeping workshop covering cash records, member share capital, and preparing financial statements for the annual general assembly.'],
        ['Vaccination drive reaches four barangays', 'The Dulag Health Workers Association supported the municipal health office in a vaccination drive for children and older residents in four barangays, recording more than 500 doses in a single weekend.'],
        ['Youth volunteers launch recycling drive', 'Basing Youth for Environment opened weekly drop-off points for plastic bottles and cartons, and visited two elementary schools to teach pupils how to separate waste at home.'],
        ['Call for nominees to Local Special Bodies', 'Accredited organizations may nominate representatives to the municipal Local Special Bodies, such as the Development Council and the Health Board. Nomination forms are available at the Civil Society Desk Office.'],
        ['Farmers receive seeds through community sharing day', 'The Malawa Farmers Association held its seed sharing day, distributing rice and vegetable seeds to smallholder farmers and demonstrating organic fertilizer preparation.'],
        ['CSO Assembly recognizes most active organizations', 'The annual CSO Assembly recognized organizations with the most verified community activities over the past year. Organizations were also briefed on the accreditation calendar for the coming year.'],
    ];

    /** Common Filipino names for demo people, so names in parts look like the real thing. */
    private const LAST_NAMES = ['Dela Cruz', 'Santos', 'Reyes', 'Bautista', 'Garcia', 'Mendoza', 'Ramos', 'Aquino', 'Fernandez', 'Castillo',
        'Villanueva', 'Soriano', 'Caguioa', 'De Guzman', 'Rosario', 'Quinto', 'Mamaril', 'Fajardo', 'Biagtan', 'Ferrer', 'Tamayo', 'Ocampo', 'Navarro', 'Pascual'];

    private const FIRST_NAMES = ['Maria', 'Juan', 'Ana', 'Jose', 'Carla', 'Paolo', 'Angelica', 'Jerome', 'Kristine', 'Rogelio', 'Teresita', 'Noel',
        'Marivic', 'Arnel', 'Liza', 'Ricardo', 'Edna', 'Cesar', 'Lea', 'Daniel', 'Joy', 'Ramon', 'Grace', 'Ronald', 'Rhea'];

    private const OFFICER_POSITIONS = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor', 'Member', 'Member', 'Member'];

    /** A demo person's name in parts. */
    public static function person(): array
    {
        return [
            'last_name' => fake()->randomElement(self::LAST_NAMES),
            'first_name' => fake()->randomElement(self::FIRST_NAMES),
            'middle_name' => fake()->randomElement(self::LAST_NAMES),
        ];
    }

    /** The member list: the representative first (from the account), then $others officers and members. */
    public static function seedMembers(Organization $organization, User $representative, int $others = 5): void
    {
        $organization->members()->create([
            'last_name' => $representative->last_name ?? 'Representative',
            'first_name' => $representative->first_name ?? $representative->name,
            'middle_name' => $representative->middle_name,
            'position' => \App\Models\OrganizationMember::REPRESENTATIVE,
            'contact_number' => ApplicationSteps::localPhone($representative->phone),
            'email' => $representative->email,
        ]);
        foreach (array_slice(self::OFFICER_POSITIONS, 0, $others) as $position) {
            $organization->members()->create(self::person() + [
                'position' => $position,
                'contact_number' => '09'.fake()->numerify('## ### ####'),
            ]);
        }
    }

    /** Hash of the password every demo account gets, worked out once. */
    private string $passwordHash;

    public function run(): void
    {
        $this->passwordHash = Hash::make(self::password());

        $admin = User::factory()->admin()->create([
            'name' => 'PESO Administrator',
            'email' => 'admin@lingayen.gov.ph',
            'password' => $this->passwordHash,
        ]);

        $this->accreditedOrganizations($admin);
        $this->organizationsInReview();
        $this->rejectedApplicant();
        $this->brandNewRegistrant();
        $this->accountStates($admin);
        $this->call(ExpiredAccreditationSeeder::class);
        $this->applicationInProgress();
        $this->newsAndReports($admin);
        $this->notices($admin);
        $this->sheetsForWaitingActivities();
        $this->computeScores();
    }

    /**
     * The demo accounts' password. On a development computer, DEMO_PASSWORD (or the public default).
     * On a live server only with ALLOW_DEMO_DATA=true and a private DEMO_PASSWORD of at least 12
     * characters: seeding with the published password would hand anyone an admin login.
     */
    public static function password(): string
    {
        $custom = (string) config('app.demo_password');

        if (! app()->isProduction()) {
            return $custom !== '' ? $custom : self::DEMO_PASSWORD;
        }

        if (! config('app.allow_demo_data')) {
            throw new \RuntimeException('Refusing to seed demo accounts in production. For a defense demo set ALLOW_DEMO_DATA=true and a private DEMO_PASSWORD in .env; for real use run `php artisan app:create-admin` instead.');
        }
        if ($custom === self::DEMO_PASSWORD || mb_strlen($custom) < 12) {
            throw new \RuntimeException('Refusing to seed demo accounts in production with the published or a short password. Set DEMO_PASSWORD in .env to a private password of at least 12 characters.');
        }

        return $custom;
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
            ['Dulag Health Workers Association', 'Health', 'Dulag',
                'Barangay health workers who run maternal care visits, vaccination drives, and first aid training.',
                ['vaccination drive', 'first aid training', 'prenatal check-up caravan']],
            ['Namolan Mussel Growers Cooperative', 'Cooperative', 'Namolan',
                'Pools members\' mussel harvests for fair pricing and trains them in safe post-harvest handling.',
                ['post-harvest handling training', 'cooperative general assembly', 'bookkeeping workshop']],
            ['Capandanan Kababaihan Association', "KALIPI (Women's)", 'Capandanan',
                'Helps women start home-based businesses and speaks up on family welfare in the barangay.',
                ['microenterprise training', 'family welfare forum', 'women\'s savings group meeting']],
            ['Talogtog Senior Citizens Federation', 'Senior Citizen', 'Talogtog',
                'Looks after older residents through home visits, social pension assistance, and exercise groups.',
                ['senior exercise morning', 'social pension assistance desk', 'home visits to bedridden seniors']],
            ['Basing Youth for Environment', 'Independent Organizations', 'Basing',
                'Young volunteers who plant trees, run recycling drives, and teach children about waste segregation.',
                ['tree planting', 'recycling drive', 'waste segregation lesson for children']],
            ['Rosario TODA', 'TODA', 'Rosario',
                'Represents tricycle drivers in Rosario and keeps the terminal orderly and safe.',
                ['terminal clean-up', 'road safety seminar', 'driver discipline orientation']],
            ['Lingayen OFW Families Circle', 'OFW', 'Poblacion',
                'Supports families of overseas Filipino workers with financial literacy and reintegration help.',
                ['financial literacy seminar', 'reintegration counselling session', 'OFW family day']],
        ];

        // One organization is deliberately hidden to demonstrate directory moderation.
        $hiddenIndex = 5;

        foreach ($profiles as $index => [$name, $sector, $barangay, $advocacy, $activityTitles]) {
            $user = User::factory()->create([
                ...self::person(),
                'email' => 'rep'.($index + 1).'@example.ph',
                'password' => $this->passwordHash,
            ]);

            $organization = Organization::factory()->for($user)->create([
                'name' => $name,
                'sector' => $sector,
                'barangay' => $barangay,
                'advocacy' => $advocacy,
                'public_visibility' => $index !== $hiddenIndex,
            ]);

            self::seedMembers($organization, $user, fake()->numberBetween(5, 8));

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
            ['not_endorsed', 'Tonton Neighborhood Association', 'Independent Organizations', 'Tonton',
                'Keeps Tonton\'s streets clean and lit, and organizes the barangay\'s neighborhood watch.'],
            ['first_reading', 'Estanza Fisherfolk Alliance', 'Farmers and Fisherfolks', 'Estanza',
                'Unites small-scale fishers in Estanza to protect fishing grounds and sell their catch at fair prices.'],
            ['second_reading', 'Libsong Health Advocates', 'Health', 'Libsong East',
                'Volunteers who run health education sessions and help residents reach the rural health unit.'],
            ['third_reading', 'Maniboc TODA', 'TODA', 'Maniboc',
                'Represents tricycle operators and drivers serving Maniboc and the routes to the town proper.'],
        ];

        foreach ($stages as $index => [$stage, $name, $sector, $barangay, $advocacy]) {
            $user = User::factory()->create([
                ...self::person(),
                'email' => 'applicant'.($index + 1).'@example.ph',
                'password' => $this->passwordHash,
            ]);

            $organization = Organization::factory()->for($user)->create([
                'name' => $name,
                'sector' => $sector,
                'barangay' => $barangay,
                'advocacy' => $advocacy,
            ]);

            self::seedMembers($organization, $user, 5);

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
            'last_name' => 'Pascual', 'first_name' => 'Rhea', 'middle_name' => 'Tamayo',
            'email' => 'rejected@example.ph',
            'password' => $this->passwordHash,
        ]);

        $organization = Organization::factory()->for($user)->create([
            'name' => 'Wawa Community Circle',
            'sector' => 'Independent Organizations',
            'barangay' => 'Wawa',
            'advocacy' => 'Brings Wawa residents together for clean-up days, feeding programmes, and disaster preparedness drills.',
        ]);

        self::seedMembers($organization, $user);

        $application = ApplicationModel::factory()->for($organization)->rejected()->create([
            'submitted_by' => $user->id,
        ]);

        $this->documents($organization, $application);
    }

    /** Registered through the public form but has not filed an application yet (empty-state walkthrough). */
    private function brandNewRegistrant(): void
    {
        $user = User::factory()->create([
            'last_name' => 'Bautista', 'first_name' => 'Lorna', 'middle_name' => 'Ramos',
            'email' => 'new@example.ph',
            'password' => $this->passwordHash,
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
            'advocacy' => 'Helps Aliwekwek\'s rice and vegetable farmers share equipment and learn better farming methods.',
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
            'last_name' => 'Aquino', 'first_name' => 'Ramon', 'middle_name' => 'Villanueva',
            'email' => 'invited@example.ph',
            'password' => Hash::make(Str::random(64)),
        ]);
        Organization::factory()->for($invited)->create([
            'name' => 'Libsong East OFW Families',
            'sector' => 'OFW',
            'barangay' => 'Libsong East',
            'advocacy' => 'Supports families of overseas workers in Libsong East with savings groups and counselling.',
        ]);

        $deactivated = User::factory()->create([
            'last_name' => 'Lim', 'first_name' => 'Teresa', 'middle_name' => 'Santos',
            'email' => 'deactivated@example.ph',
            'password' => $this->passwordHash,
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
            'advocacy' => 'Young volunteers who tutor children and help during barangay events and relief operations.',
        ]);

        AuditLog::record('account.deactivated', $deactivated, ['reason' => $deactivated->deactivation_reason], $admin);
    }

    /**
     * Registered and partway through applying (pre-final defense: registration and the application
     * as one set of steps): members listed, the accreditation form not yet certified, so signing in
     * as draft@example.ph resumes on step 4.
     */
    private function applicationInProgress(): void
    {
        $user = User::factory()->create([
            'last_name' => 'Navarro', 'first_name' => 'Paolo', 'middle_name' => 'Ferrer',
            'email' => 'draft@example.ph',
            'password' => $this->passwordHash,
        ]);
        $user->forceFill(['privacy_consented_at' => now()->subDays(2)])->save();

        $organization = Organization::factory()->for($user)->create([
            'name' => 'Balococ Youth Volunteers',
            'sector' => 'Independent Organizations',
            'barangay' => 'Balococ',
            'advocacy' => 'Young volunteers who run reading sessions for children and help during barangay clean-ups.',
        ]);
        self::seedMembers($organization, $user, 6);
        ApplicationSteps::start($organization, $user);
    }

    /** Two notices the Desk Office sent, so representatives have something under Notifications. */
    private function notices(User $admin): void
    {
        foreach ([
            ['accredited', null, 'CSO Assembly on 15 December', "All accredited organizations are invited to the year-end CSO Assembly at the Municipal Hall, 9:00 AM.\n\nPlease send one officer besides the representative."],
            ['sector', 'TODA', 'Road safety seminar for TODA members', 'The municipal traffic office holds a road safety refresher for tricycle drivers next Saturday at the Poblacion terminal.'],
        ] as [$audience, $value, $title, $body]) {
            $recipients = OfficeNotice::recipients($audience, $value)->get();
            $notice = OfficeNotice::create([
                'sender_id' => $admin->id, 'title' => $title, 'body' => $body,
                'audience' => $audience, 'audience_value' => $value, 'recipients' => $recipients->count(), 'emailed' => false,
            ]);
            Notification::send($recipients, new OfficeNoticeSent($notice));
        }
    }

    /**
     * Sign-in sheets on most waiting activities, then the free checks (App\Services\SheetChecks), so the
     * Verify activities page shows both outcomes: every fourth has no sheet, and one reuses another's
     * file. The AI reading is not faked here; it runs only with a key.
     */
    private function sheetsForWaitingActivities(): void
    {
        $first = null;
        foreach (Activity::with('organization')->where('status', 'pending')->orderBy('id')->get() as $i => $activity) {
            if ($i % 4 === 3) {
                SheetChecks::run($activity);

                continue;
            }
            $text = "ATTENDANCE / SIGN-IN SHEET. {$activity->organization->name}. Activity: {$activity->title}. Date: {$activity->activity_date->format('F j, Y')}.";
            $pdf = $i === 5 && $first ? $first : self::pdf('pdf.sample-document', [
                'organization' => $activity->organization,
                'label' => 'Attendance / sign-in sheet',
                'text' => $text,
            ]);
            $first ??= $pdf;

            $path = "activity-evidence/{$activity->organization_id}/sample-{$activity->id}.pdf";
            Storage::disk('local')->put($path, $pdf);
            $activity->update([
                'evidence_path' => $path,
                'evidence_filename' => 'sign-in-sheet.pdf',
                'evidence_mime' => 'application/pdf',
                'evidence_hash' => hash('sha256', $pdf),
            ]);
            SheetChecks::run($activity, $text);
        }
    }

    private function newsAndReports(User $admin): void
    {
        $accredited = Organization::where('public_visibility', true)->take(3)->get();


        foreach (self::NEWS_POSTS as $index => [$title, $body]) {
            $post = NewsPost::factory()->create([
                'author_id' => $admin->id,
                'title' => $title,
                'slug' => \Illuminate\Support\Str::slug($title),
                'body' => $body,
                // Newest first, roughly a week apart, so the Resources news list runs to three pages.
                'published_at' => now()->subDays($index * 7 + 2),
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

        foreach ([now()->year - 1, now()->year - 2] as $age => $year) {
            $title = "CSO Accreditation and Activity Report {$year}";
            $path = "annual-reports/sample-{$year}.pdf";
            Storage::disk('local')->put($path, self::pdf('pdf.annual-report', ['title' => $title, 'year' => $year] + $this->sampleReportFigures($age)));

            AnnualReport::create([
                'title' => $title,
                'year' => $year,
                'file_path' => $path,
                'uploaded_by' => $admin->id,
            ]);
        }
    }

    /**
     * Invented but consistent figures for a sample annual report: the accredited organizations as
     * they are now, with smaller numbers the further back the year ($age 0 = last year).
     */
    private function sampleReportFigures(int $age): array
    {
        $scale = $age === 0 ? 1.0 : 0.6;
        $organizations = Organization::whereHas('accreditations')->orderBy('name')->get(['name', 'sector', 'barangay'])
            ->map(fn (Organization $o) => [
                'name' => $o->name, 'sector' => $o->sector, 'barangay' => $o->barangay,
                'activities' => max(1, (int) round(fake()->numberBetween(4, 18) * $scale)),
            ]);
        if ($age > 0) {
            $organizations = $organizations->take((int) ceil($organizations->count() * $scale));
        }

        $sectors = $organizations->groupBy('sector')->map(fn ($group, $sector) => [
            'sector' => $sector,
            'organizations' => $group->count(),
            'activities' => $group->sum('activities'),
            'residents' => $group->sum('activities') * fake()->numberBetween(25, 70),
            'independent' => $sector === 'TODA' ? fake()->numberBetween(8, 20) : fake()->numberBetween(45, 80),
        ])->sortByDesc('activities')->values();

        return [
            'totals' => [
                'accredited' => $organizations->count(),
                'new' => (int) round($organizations->count() * ($age === 0 ? 0.4 : 0.7)),
                'renewed' => (int) round($organizations->count() * ($age === 0 ? 0.3 : 0.1)),
                'activities' => $sectors->sum('activities'),
                'residents' => $sectors->sum('residents'),
                'barangays' => $organizations->pluck('barangay')->unique()->count(),
            ],
            'sectors' => $sectors,
            'top' => $organizations->sortByDesc('activities')->take(5)->values(),
            'highlights' => $age === 0 ? [
                'The quarterly coastal clean-up grew to four barangays, led by fisherfolk and youth organizations.',
                'Senior citizens\' groups held monthly wellness days and a medicine assistance programme.',
                'Online filing opened: organizations could apply for accreditation and log activities from the CSO Desk Office website.',
                'Women\'s organizations ran livelihood trainings and an anti-VAWC awareness forum in every district.',
            ] : [
                'The Desk Office completed the first full list of CSOs operating in Lingayen.',
                'Accreditation requirements were simplified to five documents, with assisted filing at the office.',
                'Farmers\' and fisherfolk associations joined the municipal agriculture fair.',
            ],
        ];
    }

    /**
     * Placeholder uploads with realistic OCR pre-check results. $ocr overrides the text "read" from
     * a requirement, to show the reviewer's flags (a wrong file, a handwritten scan).
     */
    private function documents(Organization $organization, ApplicationModel $application, bool $expiring = false, array $ocr = []): void
    {
        $readText = [
            'accreditation_form' => "ACCREDITATION FORM For New Application (CSO). Name of Organization: {$organization->name}. Name of Representative. Place of Operation: Barangay {$organization->barangay}.",
            'officers_members_list' => 'List of Officers and Members. Position: President, Secretary, Treasurer, Auditor.',
            'constitution_bylaws' => "Constitution and By-Laws of {$organization->name}. Article I. Section 1. Membership.",
            'fee_receipt' => 'Official Receipt. Office of the Municipal Treasurer. Amount: PHP 1,000.00. Payment received.',
            'dole_sec_certification' => 'Certificate of Registration. Department of Labor and Employment.',
        ];

        foreach (array_keys(config('document_types')) as $index => $type) {
            $check = DocumentPrecheck::evaluate($type, array_key_exists($type, $ocr) ? $ocr[$type] : $readText[$type], $organization->name);

            $path = "documents/{$organization->id}/sample-{$application->id}-{$type}.pdf";
            Storage::disk('local')->put($path, self::pdf('pdf.sample-document', [
                'organization' => $organization,
                'label' => Document::label($type),
                'text' => array_key_exists($type, $ocr) ? $ocr[$type] : $readText[$type],
            ]));

            $organization->documents()->create([
                'ocr_status' => $check['status'],
                'ocr_details' => $check['details'],
                'application_id' => $application->id,
                'document_type' => $type,
                'file_path' => $path,
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

    /** A sample PDF with only the font glyphs it uses (about 30 KB instead of 850 KB). */
    private static function pdf(string $view, array $data): string
    {
        return Pdf::setOption(['isFontSubsettingEnabled' => true])->loadView($view, $data)->output();
    }

    private function computeScores(): void
    {
        $calculator = app(PerformanceScoreCalculator::class);

        Organization::each(fn (Organization $organization) => $calculator->recalculate($organization));
    }
}
