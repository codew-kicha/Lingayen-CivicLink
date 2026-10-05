<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentPrecheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Milestone 2 Phase 5: advisory OCR pre-check of uploaded requirements (PRD §4.1). */
class DocumentPrecheckTest extends TestCase
{
    use RefreshDatabase;

    private const RECEIPT = 'OFFICIAL RECEIPT. Municipal Treasurer, Lingayen. Amount paid: PHP 1,000.00 for accreditation.';

    public function test_matching_ignores_case_spacing_and_punctuation(): void
    {
        $result = DocumentPrecheck::evaluate('constitution_bylaws', 'CONSTITUTION AND BY - LAWS of the Wawa Fisherfolk Association. ARTICLE I, Section 1.', 'Wawa Fisherfolk Association');

        $this->assertSame('matched', $result['status']);
        $this->assertSame(['constitution', 'bylaws', 'article', 'section'], $result['details']['found']);
        $this->assertTrue($result['details']['organization_named']);
    }

    public function test_a_document_in_the_wrong_slot_is_a_mismatch(): void
    {
        $result = DocumentPrecheck::evaluate('constitution_bylaws', self::RECEIPT);

        $this->assertSame('mismatch', $result['status']);
        $this->assertSame([], $result['details']['found']);
    }

    public function test_too_little_text_is_unreadable_and_no_text_is_not_checked(): void
    {
        $this->assertSame('unreadable', DocumentPrecheck::evaluate('fee_receipt', 'Rcpt ~~ 1,0OO')['status']);
        $this->assertSame('not_checked', DocumentPrecheck::evaluate('fee_receipt', null)['status']);
        $this->assertSame('not_checked', DocumentPrecheck::evaluate('fee_receipt', "  \n ")['status']);
    }

    public function test_every_requirement_has_keywords(): void
    {
        foreach (config('document_types') as $key => $type) {
            $this->assertNotEmpty($type['keywords'] ?? [], "{$key} has no pre-check keywords");
            $this->assertLessThanOrEqual(count($type['keywords']), $type['min_matches'], "{$key} can never match");
        }
    }

    public function test_submission_stores_the_server_verdict_without_the_text_and_never_blocks(): void
    {
        Storage::fake('local');
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();

        $documents = collect(config('document_types'))->map(fn ($type, $key) => UploadedFile::fake()->create("{$key}.pdf", 50, 'application/pdf'))->all();

        $this->actingAs($rep)->post(route('cso.applications.store'), [
            'type' => 'new',
            'documents' => $documents,
            'ocr_text' => [
                'fee_receipt' => self::RECEIPT,                          // right document
                'constitution_bylaws' => self::RECEIPT,                  // wrong slot, still accepted
                'accreditation_form' => 'scribbles',                     // handwritten / unreadable
                // officers_members_list: browser never finished reading
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $status = Document::pluck('ocr_status', 'document_type');
        $this->assertSame('matched', $status['fee_receipt']);
        $this->assertSame('mismatch', $status['constitution_bylaws']);
        $this->assertSame('unreadable', $status['accreditation_form']);
        $this->assertSame('not_checked', $status['officers_members_list']);

        $receipt = Document::where('document_type', 'fee_receipt')->first();
        $this->assertContains('treasur', $receipt->ocr_details['found']);
        $this->assertStringNotContainsString('OFFICIAL RECEIPT', json_encode($receipt->ocr_details));
    }

    public function test_reviewers_see_the_flags(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create(['user_id' => null]);
        $documents = collect(config('document_types'))->map(fn ($type, $key) => UploadedFile::fake()->create("{$key}.pdf", 50, 'application/pdf'))->all();

        $this->actingAs($admin)->post(route('admin.organizations.applications.store', $organization), [
            'type' => 'new', 'documents' => $documents, 'ocr_text' => ['constitution_bylaws' => self::RECEIPT],
        ]);
        $application = $organization->applications()->firstOrFail();

        $this->actingAs($admin)->get(route('admin.applications.index'))->assertSee('1 document to check');
        $this->actingAs($admin)->get(route('admin.applications.show', $application))
            ->assertSee('Check content')->assertSee('None of the expected words were found.');
    }

    public function test_oversized_ocr_text_is_rejected(): void
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        $this->actingAs($rep)->post(route('cso.applications.store'), ['type' => 'new', 'ocr_text' => ['fee_receipt' => str_repeat('a', 20001)]])
            ->assertSessionHasErrors('ocr_text.fee_receipt');
    }
}
