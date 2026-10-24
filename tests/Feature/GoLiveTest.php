<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GoLiveChecks;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Go-live safety: the checks, the first-admin command, the seeding guard and the security headers. */
class GoLiveTest extends TestCase
{
    use RefreshDatabase;

    private function check(array $results, string $label): array
    {
        return collect($results)->firstWhere('label', $label);
    }

    public function test_php_versions_without_the_php2shell_fix_fail(): void
    {
        foreach (['8.2.12' => 'fail', '8.2.35' => 'pass', '8.3.35' => 'fail', '8.3.36' => 'pass', '8.4.27' => 'pass',
            '8.5.11' => 'fail', '8.5.12' => 'pass', '8.1.30' => 'fail', '7.4.33' => 'fail'] as $version => $status) {
            $this->assertSame($status, GoLiveChecks::php($version)['status'], "PHP {$version}");
        }
    }

    public function test_debug_mode_insecure_urls_and_demo_accounts_fail(): void
    {
        config(['app.debug' => true, 'app.url' => 'http://example.gov.ph']);
        User::factory()->admin()->create(['email' => 'admin@lingayen.gov.ph', 'password' => DatabaseSeeder::DEMO_PASSWORD]);

        $results = (new GoLiveChecks)->run(probeUrls: false);

        $this->assertSame('fail', $this->check($results, 'Debug mode')['status']);
        $this->assertSame('fail', $this->check($results, 'HTTPS')['status']);
        $this->assertSame('fail', $this->check($results, 'Demo accounts')['status']);
        $this->assertStringContainsString('admin@lingayen.gov.ph', $this->check($results, 'Demo accounts')['detail']);
        $this->assertTrue(GoLiveChecks::failed($results));
    }

    public function test_a_properly_configured_server_passes_those_checks(): void
    {
        config(['app.debug' => false, 'app.url' => 'https://csodesk.example.gov.ph', 'session.secure' => true]);
        User::factory()->admin()->create(['email' => 'admin@lingayen.gov.ph', 'password' => 'A-Real-Admin-Pass-99']);

        $results = (new GoLiveChecks)->run(probeUrls: false);

        foreach (['Debug mode', 'HTTPS', 'Demo accounts', 'App key', 'Writable folders'] as $label) {
            $this->assertSame('pass', $this->check($results, $label)['status'], $label);
        }
    }

    public function test_downloadable_private_files_fail_and_an_unreachable_site_only_warns(): void
    {
        config(['app.url' => 'https://csodesk.example.gov.ph']);

        $mode = 'exposed';
        Http::fake(function ($request) use (&$mode) {
            return match ($mode) {
                'exposed' => str_ends_with($request->url(), '/.env') ? Http::response("APP_KEY=base64:abc
") : Http::response('Not found', 404),
                'protected' => Http::response('<html>Page not found</html>', 404),
                'blocked' => throw new ConnectionException('blocked'),
            };
        });

        $this->assertSame('fail', $this->check((new GoLiveChecks)->run(), 'Private files')['status']);
        $mode = 'protected';
        $this->assertSame('pass', $this->check((new GoLiveChecks)->run(), 'Private files')['status']);
        $mode = 'blocked';
        $this->assertSame('warn', $this->check((new GoLiveChecks)->run(), 'Private files')['status']);
    }

    public function test_the_command_fails_until_the_server_is_ready(): void
    {
        $this->artisan('app:go-live-check --no-probe')->expectsOutputToContain('Not ready to go live')->assertExitCode(1);
    }

    public function test_the_first_administrator_is_created_from_the_command_line(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Full name', 'Cezca Katrina F. Mararac')
            ->expectsQuestion('Email (their sign-in)', 'Manager@Lingayen.gov.ph')
            ->expectsQuestion('Password (at least 12 characters, mixed case, a number and a symbol)', 'Peso-Lingayen-2026')
            ->assertExitCode(0);

        $admin = User::where('email', 'manager@lingayen.gov.ph')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('Peso-Lingayen-2026', $admin->password));

        $this->artisan('app:create-admin')
            ->expectsQuestion('Full name', 'Weak')
            ->expectsQuestion('Email (their sign-in)', 'weak@lingayen.gov.ph')
            ->expectsQuestion('Password (at least 12 characters, mixed case, a number and a symbol)', 'password')
            ->assertExitCode(1);
        $this->assertDatabaseMissing('users', ['email' => 'weak@lingayen.gov.ph']);
    }

    public function test_the_demo_data_is_never_seeded_on_a_live_site(): void
    {
        $this->app['env'] = 'production';

        $this->expectExceptionMessage('Refusing to seed demo accounts in production');
        $this->app->make(DatabaseSeeder::class)->run();
    }

    public function test_the_system_check_page_is_for_admins(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.system-check'))->assertOk()
            ->assertSee('PHP version (PHP2Shell)')->assertSee('Private files')->assertSee('Not ready to go live');
        $this->actingAs(User::factory()->create())->get(route('admin.system-check'))->assertForbidden();
    }

    public function test_the_php_version_is_not_advertised_and_https_is_enforced_on_the_live_site(): void
    {
        $this->get('/')->assertHeaderMissing('X-Powered-By')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
