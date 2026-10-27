<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Milestone 2 Phase 8: attacks across the whole app (account takeover, privilege escalation,
 * enumeration, injection, abuse), complementing the per-feature checks in SecurityTest.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Lingayen-Coast-2026';

    private function accredit(Organization $organization): void
    {
        $application = ApplicationModel::factory()->for($organization)->approved()->create();
        $organization->accreditations()->create([
            'application_id' => $application->id, 'verification_code' => 'SEC-'.$organization->id,
            'status' => 'active', 'active_org_marker' => $organization->id,
            'issued_at' => now()->subYear()->toDateString(), 'expires_at' => now()->addYears(2)->toDateString(),
        ]);
    }

    // Privilege escalation through mass assignment

    public function test_a_rep_cannot_promote_or_reactivate_themselves_through_their_profile(): void
    {
        $rep = User::factory()->create();

        $this->actingAs($rep)->patch(route('profile.update'), [
            'name' => 'Rep', 'email' => $rep->email, 'phone' => '09171234567',
            'role' => 'admin', 'is_active' => false, 'email_verified_at' => null, 'deactivated_at' => now(),
        ])->assertSessionHasNoErrors();

        $rep->refresh();
        $this->assertSame('cso_rep', $rep->role);
        $this->assertTrue($rep->is_active);
        $this->assertNotNull($rep->email_verified_at);
    }

    public function test_a_rep_cannot_relist_their_hidden_organization_or_move_it_to_another_account(): void
    {
        $rep = User::factory()->create();
        $other = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create(['public_visibility' => false]);

        $this->actingAs($rep)->patch(route('cso.profile.update'), [
            'name' => $organization->name, 'sector' => $organization->sector, 'barangay' => $organization->barangay,
            'public_visibility' => true, 'user_id' => $other->id,
        ])->assertSessionHasNoErrors();

        $organization->refresh();
        $this->assertFalse($organization->public_visibility);
        $this->assertSame($rep->id, $organization->user_id);
    }

    public function test_a_rep_cannot_log_an_activity_as_already_verified_or_for_another_organization(): void
    {
        $rep = User::factory()->create();
        $mine = Organization::factory()->for($rep)->accredited()->create();
        $theirs = Organization::factory()->create();

        $this->actingAs($rep)->post(route('cso.activities.store'), [
            'title' => 'Clean-up', 'description' => 'Shoreline.', 'activity_date' => now()->subDay()->toDateString(),
            'activity_source' => 'independent',
            'status' => 'verified', 'verified_by' => $rep->id, 'organization_id' => $theirs->id,
        ])->assertSessionHasNoErrors();

        $activity = Activity::firstOrFail();
        $this->assertSame('pending', $activity->status);
        $this->assertNull($activity->verified_by);
        $this->assertSame($mine->id, $activity->organization_id);
    }

    public function test_an_activity_cannot_be_verified_twice_or_reopened(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->verified()->create();

        $this->actingAs($admin)->post(route('admin.activities.verify', $activity), ['activity_source' => 'independent'])->assertStatus(422);
        $this->actingAs($admin)->post(route('admin.activities.reject', $activity), ['rejection_reason' => 'Changed my mind about it.'])->assertStatus(422);
        $this->assertSame('verified', $activity->fresh()->status);
    }

    // Account takeover and enumeration

    public function test_login_attempts_are_throttled_after_five_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $_) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_login_gives_the_same_answer_for_an_unknown_email_and_a_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => 'nobody@example.ph', 'password' => 'whatever-123']);
        $unknown = session('errors')->first('email');
        $this->post(route('logout'));

        $this->post(route('login'), ['email' => $user->email, 'password' => 'whatever-123']);
        $this->assertSame($unknown, session('errors')->first('email'));
    }

    public function test_password_reset_does_not_reveal_whether_an_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.ph']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_password_reset_does_not_bring_back_a_deactivated_account(): void
    {
        $user = User::factory()->create(['is_active' => false, 'deactivated_at' => now()]);
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token, 'email' => $user->email,
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
        ]);

        $this->assertFalse($user->fresh()->is_active);
        $this->post(route('login'), ['email' => $user->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logging_in_issues_a_new_session_id(): void
    {
        $user = User::factory()->create();
        $this->get(route('login'));
        $before = session()->getId();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticated();
        $this->assertNotSame($before, session()->getId());
    }

    public function test_an_invitation_link_for_one_account_cannot_be_reused_for_another(): void
    {
        $invited = User::factory()->unverified()->create();
        $victim = User::factory()->unverified()->create();
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('invitation.store', now()->addHour(), ['user' => $invited, 'for' => \App\Notifications\AccountInvitation::fingerprint($invited)]);

        $this->post(str_replace("/invitation/{$invited->id}", "/invitation/{$victim->id}", $url), [
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
        ])->assertForbidden();

        $this->assertNull($victim->fresh()->email_verified_at);
    }

    // Injection

    public function test_user_content_is_escaped_on_public_pages(): void
    {
        $organization = Organization::factory()->create(['name' => '<script>alert("org")</script>', 'advocacy' => '<img src=x onerror=alert(1)>']);
        $this->accredit($organization);
        Activity::factory()->for($organization)->verified()->create(['title' => '<script>alert("title")</script>']);

        $page = $this->get(route('directory.show', $organization))->assertOk();

        $page->assertDontSee('<script>alert("org")</script>', false);
        $page->assertDontSee('<img src=x onerror=alert(1)>', false);
        $page->assertDontSee('<script>alert("title")</script>', false);
        $page->assertSee('&lt;script&gt;', false);
    }

    public function test_spreadsheet_exports_never_turn_organization_names_into_formulas(): void
    {
        $organization = Organization::factory()->create(['name' => '=HYPERLINK("http://evil.example","Click")']);
        $this->accredit($organization);
        Activity::factory()->for($organization)->verified()->create(['activity_date' => now()->subWeek()]);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.analytics.excel'));

        $sheet = IOFactory::load($response->getFile()->getPathname())->getSheetByName('Top contributors');
        $cell = $sheet->getCell('A2');
        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('=HYPERLINK("http://evil.example","Click")', $cell->getValue());
    }

    // Uploads and private data

    public function test_a_disguised_file_is_rejected_even_with_a_pdf_name(): void
    {
        Storage::fake('local');
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        // A real file on disk, so the type is sniffed from its content (fake uploads trust the name).
        // Harmless PHP on purpose: a real web-shell line is quarantined by Windows Defender before
        // PHP can read it, which made this test fail on Windows for the wrong reason.
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, "<?php\necho 'This is not a receipt.';\n");

        $this->actingAs($rep)->post(route('cso.applications.store'), [
            'type' => 'new',
            'documents' => ['fee_receipt' => new UploadedFile($path, 'receipt.pdf', 'application/pdf', null, true)],
        ])->assertSessionHasErrors('documents.fee_receipt');
    }

    public function test_uploads_are_stored_privately_and_downloaded_as_attachments(): void
    {
        Storage::fake('local');
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();
        $documents = collect(config('document_types'))->map(fn ($t, $key) => UploadedFile::fake()->create("{$key}.pdf", 20, 'application/pdf'))->all();

        $this->actingAs($rep)->post(route('cso.applications.store'), ['type' => 'new', 'documents' => $documents]);
        $document = \App\Models\Document::firstOrFail();

        $this->assertStringStartsNotWith('public', $document->file_path);
        $this->assertContains($this->get('/storage/'.$document->file_path)->status(), [403, 404]);
        // A PDF opens in the browser tab, typed as recorded at upload and never sniffed.
        $response = $this->actingAs($rep)->get(route('documents.download', $document))->assertOk()
            ->assertHeader('content-type', 'application/pdf')->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringStartsWith('inline', $response->headers->get('content-disposition'));

        // Anything not viewable is downloaded instead.
        $document->update(['mime_type' => 'application/octet-stream']);
        $this->assertStringStartsWith('attachment', $this->actingAs($rep)->get(route('documents.download', $document))->headers->get('content-disposition'));
    }

    public function test_representative_contact_details_never_appear_publicly(): void
    {
        $rep = User::factory()->create(['email' => 'private.rep@example.ph', 'phone' => '+639171112222']);
        $organization = Organization::factory()->for($rep)->create();
        $this->accredit($organization);

        foreach ([route('directory.show', $organization), route('directory'), route('home')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('private.rep@example.ph')->assertDontSee('+639171112222');
        }
    }

    // Abuse and browser protections

    public function test_the_contact_form_is_rate_limited(): void
    {
        $message = ['name' => 'Juan', 'email' => 'juan@example.ph', 'subject' => 'Question', 'message' => 'How do we renew our accreditation?'];

        foreach (range(1, 5) as $_) {
            $this->post(route('contact.send'), $message)->assertRedirect();
        }

        $this->post(route('contact.send'), $message)->assertStatus(429);
    }

    public function test_every_page_sends_baseline_security_headers(): void
    {
        foreach ([route('home'), route('login')] as $url) {
            $this->get($url)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }
}
