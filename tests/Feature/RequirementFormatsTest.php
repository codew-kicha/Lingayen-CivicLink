<?php

namespace Tests\Feature;

use App\Exports\ReportSheet;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/** Downloadable formats for the requirements, linked from the Apply pop-up. */
class RequirementFormatsTest extends TestCase
{
    use RefreshDatabase;

    private function rep(): User
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create(['name' => 'Wawa Weavers']);
        $organization->members()->createMany([
            ['name' => 'Ana Reyes', 'position' => 'President', 'contact_number' => '09171112222'],
            ['name' => 'Ben Cruz', 'position' => 'Secretary', 'contact_number' => '09183334444'],
        ]);

        return $rep;
    }

    public function test_the_apply_pop_up_links_each_format(): void
    {
        $rep = $this->rep();

        $this->actingAs($rep)->get(route('cso.applications.index', ['apply' => 1]))->assertOk()
            ->assertSee(route('accreditation.form', 'new'), false)
            ->assertSee(route('accreditation.form', 'renewal'), false)
            ->assertSee(route('requirements.format', ['officers_members_list', 'pdf']), false)
            ->assertSee(route('requirements.format', ['officers_members_list', 'xlsx']), false)
            ->assertSee(route('requirements.format', ['constitution_bylaws', 'pdf']), false)
            ->assertDontSee('fee_receipt/format', false);
    }

    public function test_the_officers_list_downloads_as_pdf_and_as_excel_filled_from_the_profile(): void
    {
        $rep = $this->rep();

        $pdf = $this->actingAs($rep)->get(route('requirements.format', ['officers_members_list', 'pdf']))->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString('officers-and-members-wawa-weavers.pdf', $pdf->headers->get('content-disposition'));

        Excel::fake();
        $this->actingAs($rep)->get(route('requirements.format', ['officers_members_list', 'xlsx']))->assertOk();
        Excel::assertDownloaded('officers-and-members-wawa-weavers.xlsx', function (ReportSheet $sheet) {
            $rows = $sheet->array();

            return $rows[0][1] === 'Ana Reyes' && $rows[1][3] === '09183334444' && count($rows) === 2 + 10;
        });
    }

    public function test_the_constitution_sample_downloads_as_pdf(): void
    {
        $response = $this->actingAs($this->rep())->get(route('requirements.format', ['constitution_bylaws', 'pdf']))->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('constitution-and-by-laws-wawa-weavers.pdf', $response->headers->get('content-disposition'));
    }

    public function test_a_representative_only_ever_gets_their_own_organization(): void
    {
        $rep = $this->rep();
        $other = Organization::factory()->create(['name' => 'Someone Else Association']);

        Excel::fake();
        $this->actingAs($rep)->get(route('requirements.format', ['officers_members_list', 'xlsx', 'organization' => $other->id]))->assertOk();
        Excel::assertDownloaded('officers-and-members-wawa-weavers.xlsx');
    }

    public function test_formats_need_a_sign_in_and_exist_only_where_offered(): void
    {
        $this->get(route('requirements.format', ['officers_members_list', 'pdf']))->assertRedirect(route('login'));

        $rep = $this->rep();
        $this->actingAs($rep)->get('/requirements/constitution_bylaws/format.xlsx')->assertNotFound();
        $this->actingAs($rep)->get('/requirements/fee_receipt/format.pdf')->assertNotFound();
    }

    public function test_office_staff_get_a_named_organization_and_it_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = $this->rep()->organization;

        Excel::fake();
        $this->actingAs($admin)->get(route('requirements.format', ['officers_members_list', 'xlsx', 'organization' => $organization->id]))->assertOk();
        Excel::assertDownloaded('officers-and-members-wawa-weavers.xlsx');
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.members_printed', 'subject_id' => $organization->id]);

        // The admin's "File an application" pop-up links the formats for that organization.
        $this->actingAs($admin)->get(route('admin.organizations.edit', $organization))
            ->assertSee(route('requirements.format', ['officers_members_list', 'pdf', 'organization' => $organization->id]), false);
    }
}
