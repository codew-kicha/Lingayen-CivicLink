<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §16 requires every screen to be live and functional, with no dead links. This walks all
 * three role experiences and fails on any 500 or missing route.
 */
class ScreenSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_screen_renders(): void
    {
        $organization = Organization::factory()->create();
        $application = ApplicationModel::factory()->for($organization)->approved()->create();
        $organization->accreditations()->create([
            'application_id' => $application->id,
            'verification_code' => 'SMOKE-TEST-01',
            'status' => 'active',
            'active_org_marker' => $organization->id,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addYears(3)->toDateString(),
        ]);

        foreach (['home', 'about', 'accreditation', 'directory', 'resources', 'contact'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('directory.show', $organization))->assertOk();
    }

    public function test_every_admin_screen_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->create();
        Activity::factory()->create();

        foreach ([
            'admin.dashboard',
            'admin.applications.index',
            'admin.activities.index',
            'admin.organizations.index',
            'admin.scorecards.index',
            'admin.analytics',
            'admin.accounts.index',
            'admin.organizations.create',
            'admin.audit.index',
            'profile.edit',
        ] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.applications.show', $application))->assertOk();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('admin.accounts.create'))->assertOk();

        // Edit screen in each account state: with a login, without one, and invitation pending.
        $withoutLogin = Organization::factory()->create(['user_id' => null]);
        $pending = Organization::factory()->for(User::factory()->unverified())->create();
        foreach ([$application->organization, $withoutLogin, $pending] as $organization) {
            $this->actingAs($admin)->get(route('admin.organizations.edit', $organization))->assertOk();
        }

        // Assisted encoding and import.
        $this->actingAs($admin)->get(route('admin.organizations.applications.create', $withoutLogin))->assertOk();
        $this->actingAs($admin)->get(route('admin.organizations.activities.create', $withoutLogin))
            ->assertOk()->assertSee('Partner organizations');
        $this->actingAs($admin)->get(route('admin.organizations.import'))->assertOk();
        $this->actingAs($admin)->get(route('admin.organizations.import.template'))->assertOk();
    }

    public function test_auth_screens_render(): void
    {
        foreach (['login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $invited = User::factory()->unverified()->create();
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('invitation.show', now()->addHour(), ['user' => $invited]))
            ->assertOk()->assertSee('Set your password');

        $used = User::factory()->create();
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('invitation.show', now()->addHour(), ['user' => $used]))
            ->assertOk()->assertSee('already been used');
    }

    public function test_the_analytics_pdf_export_produces_a_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        Activity::factory()->verified()->create();

        $response = $this->actingAs($admin)->get(route('admin.analytics.export'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_every_cso_screen_renders_with_an_organization(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();
        $application = ApplicationModel::factory()->for($organization)->create();

        foreach ([
            'cso.dashboard',
            'cso.profile.edit',
            'cso.applications.index',
            'cso.applications.create',
            'cso.activities.index',
        ] as $route) {
            $this->actingAs($rep)->get(route($route))->assertOk();
        }

        $this->actingAs($rep)->get(route('cso.applications.show', $application))->assertOk();
    }

    public function test_cso_screens_render_before_an_organization_exists(): void
    {
        $rep = User::factory()->create();

        foreach (['cso.dashboard', 'cso.profile.edit', 'cso.applications.index', 'cso.activities.index'] as $route) {
            $this->actingAs($rep)->get(route($route))->assertOk();
        }
    }

    public function test_a_new_rep_can_create_their_organization_profile(): void
    {
        $rep = User::factory()->create();

        $this->actingAs($rep)
            ->patch(route('cso.profile.update'), [
                'name' => 'Bantayan Coastal Volunteers',
                'sector' => config('sectors')[0],
                'barangay' => array_values(config('barangays'))[0],
                'advocacy' => 'Shoreline protection and clean-up drives.',
                'members' => [
                    ['name' => 'Ana Reyes', 'position' => 'President'],
                    ['name' => '', 'position' => ''],
                ],
            ])
            ->assertRedirect(route('cso.profile.edit'));

        $organization = $rep->refresh()->organization;
        $this->assertSame('Bantayan Coastal Volunteers', $organization->name);
        $this->assertCount(1, $organization->members);
    }
}
