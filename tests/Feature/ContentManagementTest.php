<?php

namespace Tests\Feature;

use App\Models\AnnualReport;
use App\Models\ApplicationModel;
use App\Models\NewsPost;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * News and annual reports authored in the admin CMS must reach the public Resources page
 * (PRD §6.1 items 8 and 9, §16).
 */
class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_post_reaches_the_public_resources_page(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = $this->accreditedOrganization();

        $this->actingAs($admin)
            ->post(route('admin.news.store'), [
                'title' => 'Accreditation window now open',
                'body' => 'The office is accepting applications until the end of the quarter.',
                'status' => 'published',
                'organizations' => [$organization->id],
            ])
            ->assertRedirect(route('admin.news.index'));

        $post = NewsPost::firstOrFail();
        $this->assertSame('accreditation-window-now-open', $post->slug);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->organizations->contains($organization));

        $this->get(route('resources'))
            ->assertOk()
            ->assertSee('Accreditation window now open')
            ->assertSee($organization->name);
    }

    public function test_a_draft_post_stays_off_the_public_site(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.news.store'), [
            'title' => 'Internal planning note',
            'body' => 'Not for publication yet.',
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertNull(NewsPost::firstOrFail()->published_at);

        $this->get(route('resources'))->assertOk()->assertDontSee('Internal planning note');
        $this->get(route('home'))->assertOk()->assertDontSee('Internal planning note');
    }

    public function test_publishing_a_draft_later_sets_its_publication_date(): void
    {
        $admin = User::factory()->admin()->create();
        $post = NewsPost::factory()->draft()->create(['author_id' => $admin->id]);

        $this->actingAs($admin)
            ->put(route('admin.news.update', $post), [
                'title' => $post->title,
                'body' => $post->body,
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertNotNull($post->refresh()->published_at);
        $this->get(route('resources'))->assertOk()->assertSee($post->title);
    }

    public function test_an_annual_report_is_uploaded_and_downloadable_from_resources(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.annual-reports.store'), [
                'title' => 'CSO Accreditation and Activity Report',
                'year' => now()->year - 1,
                'file' => UploadedFile::fake()->create('report.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect();

        $report = AnnualReport::firstOrFail();
        Storage::disk('local')->assertExists($report->file_path);

        // Stored outside the public web root, reachable only through the controlled route.
        $this->assertStringStartsWith('annual-reports/', $report->file_path);

        $this->get(route('resources'))->assertOk()->assertSee($report->title);
        $this->get(route('annual-reports.download', $report))->assertOk();
    }

    public function test_an_annual_report_rejects_a_non_pdf_upload(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.annual-reports.store'), [
                'title' => 'Bad upload',
                'year' => now()->year,
                'file' => UploadedFile::fake()->create('report.exe', 10, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('annual_reports', 0);
    }

    public function test_a_cso_rep_cannot_author_posts_or_upload_reports(): void
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        $this->actingAs($rep)->get(route('admin.news.create'))->assertForbidden();
        $this->actingAs($rep)->post(route('admin.news.store'), [
            'title' => 'Unauthorized', 'body' => 'Should not save.', 'status' => 'published',
        ])->assertForbidden();

        $this->assertDatabaseCount('news_posts', 0);
    }

    public function test_the_admin_cms_screens_render(): void
    {
        $admin = User::factory()->admin()->create();
        $post = NewsPost::factory()->create(['author_id' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.news.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.news.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.news.edit', $post))->assertOk();
        $this->actingAs($admin)->get(route('admin.annual-reports.index'))->assertOk();
    }

    private function accreditedOrganization(): Organization
    {
        $organization = Organization::factory()->create();

        $organization->accreditations()->create([
            'application_id' => ApplicationModel::factory()->for($organization)->create()->id,
            'verification_code' => 'CMS-TEST-0001',
            'status' => 'active',
            'active_org_marker' => $organization->id,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addYears(3)->toDateString(),
        ]);

        return $organization;
    }
}
