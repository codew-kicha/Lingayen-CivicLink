<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * An official receipt and the DOLE/SEC certificate have no expiry date; and a split bar shows every
 * part's percentage, small ones included.
 */
class RequirementExpiryAndChartLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_receipt_and_the_dole_sec_certificate_ask_for_no_expiry(): void
    {
        Storage::fake('local');
        Notification::fake();
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();
        $this->approveForm($this->submitForm($rep));

        $this->actingAs($rep)->get(route('cso.application.requirements.show'))->assertOk()
            ->assertSee('id="exp-constitution_bylaws"', false)
            ->assertDontSee('id="exp-fee_receipt"', false)
            ->assertDontSee('id="exp-dole_sec_certification"', false);

        // A date sent anyway is not kept; the constitution's still is.
        $later = now()->addYear()->toDateString();
        $this->uploadRequirements($rep, $this->requirementFiles(['fee_receipt', 'constitution_bylaws', 'dole_sec_certification']), [
            'expires_at' => ['fee_receipt' => $later, 'dole_sec_certification' => $later, 'constitution_bylaws' => $later],
        ])->assertSessionHasNoErrors();

        $this->assertNull(Document::where('document_type', 'fee_receipt')->value('expires_at'));
        $this->assertNull(Document::where('document_type', 'dole_sec_certification')->value('expires_at'));
        $this->assertSame($later, Document::where('document_type', 'constitution_bylaws')->first()->expires_at->toDateString());
        $this->assertFalse(Document::expires('fee_receipt'));
        $this->assertTrue(Document::expires('constitution_bylaws'));
    }

    public function test_a_small_part_of_a_split_bar_still_shows_its_percentage(): void
    {
        $html = Blade::render('<x-source-split :independent="8" :lgu="1" />');

        // Both parts carry their value (it shows whenever it fits, and a clicked part widens to fit it).
        // Each value says how long it is, so a long one hides rather than being clipped.
        $this->assertSame(2, substr_count($html, 'class="chart-seg-label'));
        $this->assertStringContainsString('<span class="chart-seg-label chart-len-3">89%</span>', $html);
        $this->assertStringContainsString('<span class="chart-seg-label chart-len-3">11%</span>', $html);
        $this->assertStringContainsString('data-[on=true]:min-w-12', $html);
    }
}
