<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use App\Services\SheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Help for verifying many activities (pre-final defense panel): free checks on every logged activity,
 * the AI reading of sign-in sheets when switched on, and verifying the consistent ones in one go.
 */
class ActivityChecksTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        SheetReader::$fake = null;
        parent::tearDown();
    }

    private function rep(): User
    {
        return Organization::factory()->accredited()->create()->user;
    }

    private function log(User $rep, array $overrides = [], ?UploadedFile $sheet = null): Activity
    {
        $this->actingAs($rep)->post(route('cso.activities.store'), $overrides + [
            'title' => 'Coastal clean-up',
            'description' => 'Members cleared the shoreline.',
            'activity_date' => now()->subDays(3)->toDateString(),
            'participants_estimate' => 20,
            'activity_source' => 'independent',
            'evidence' => $sheet ?? UploadedFile::fake()->createWithContent('sheet.pdf', 'sheet-'.uniqid()),
        ])->assertSessionHasNoErrors();

        return Activity::latest('id')->firstOrFail();
    }

    public function test_a_normal_activity_is_consistent(): void
    {
        Storage::fake('local');
        $activity = $this->log($this->rep());

        // Verified, with one small point off: no photos (they are optional).
        $this->assertSame(['consistent', 90], [$activity->check_status, $activity->check_score]);
        $this->assertSame(['No activity photos are attached.'], $activity->checkFindings());
        $this->assertContains('The sign-in sheet is not attached to any other activity.', $activity->checkConfirmations());
        $this->assertNotNull($activity->evidence_hash);
    }

    public function test_the_free_checks_flag_a_missing_sheet_a_reused_file_and_a_late_log(): void
    {
        Storage::fake('local');
        $rep = $this->rep();

        $noSheet = $this->log($rep, ['evidence' => null]);
        $this->assertSame('flagged', $noSheet->check_status);
        $this->assertContains('No sign-in sheet is attached.', $noSheet->checkFindings());

        $first = $this->log($rep, ['title' => 'Tree planting'], UploadedFile::fake()->createWithContent('a.pdf', 'the same bytes'));
        $again = $this->log($rep, [], UploadedFile::fake()->createWithContent('b.pdf', 'the same bytes'));
        $this->assertSame('consistent', $first->check_status);
        $this->assertSame('flagged', $again->check_status);
        $this->assertStringContainsString('The same file is attached to “Tree planting”', $again->checkFindings()[0]);

        $late = $this->log($rep, ['activity_date' => now()->subDays(90)->toDateString()]);
        $this->assertContains('Logged 90 days after the activity.', $late->checkFindings());
    }

    public function test_a_typed_sheet_must_show_the_date_or_the_title(): void
    {
        Storage::fake('local');
        $rep = $this->rep();
        $date = now()->subDays(3);

        $matching = $this->log($rep, ['evidence_text' => 'ATTENDANCE SHEET. Activity: Coastal clean-up drive. Date: '.$date->format('F j, Y').'. Names: Juan, Maria, Pedro.']);
        $this->assertSame('consistent', $matching->check_status);

        $other = $this->log($rep, ['evidence_text' => 'ATTENDANCE SHEET. Activity: Feeding program for children. Date: January 5, 2020. Names: Juan, Maria.']);
        $this->assertContains("The sheet doesn't show the activity's date or title.", $other->checkFindings());

        // Too little text (handwriting) proves nothing either way.
        $this->assertSame('consistent', $this->log($rep, ['evidence_text' => 'scribbles'])->check_status);
        // The text itself is never kept.
        $this->assertDatabaseMissing('activities', ['check_findings' => null, 'id' => $other->id]);
        $this->assertStringNotContainsString('Feeding program', json_encode($other->fresh()->getAttributes()));
    }

    public function test_the_ai_reading_adds_its_findings_when_switched_on(): void
    {
        Storage::fake('local');
        $this->withoutDefer();
        config(['services.anthropic.sheet_reader' => true]);
        $rep = $this->rep();

        SheetReader::$fake = fn () => ['readable' => true, 'sheet_date' => now()->subDays(3)->toDateString(), 'sheet_title' => 'Coastal clean-up', 'names_count' => 18];
        $fine = $this->log($rep)->fresh();
        $this->assertSame('consistent', $fine->check_status);
        $this->assertSame(18, $fine->ai_names_count);
        $this->assertNotNull($fine->ai_checked_at);

        SheetReader::$fake = fn () => ['readable' => true, 'sheet_date' => now()->subDays(40)->toDateString(), 'sheet_title' => null, 'names_count' => 4];
        $odd = $this->log($rep)->fresh();
        $this->assertSame('flagged', $odd->check_status);
        $this->assertContains('The sheet lists 4 names; the activity reports 20 residents reached.', $odd->checkFindings());
        $this->assertStringContainsString('The sheet is dated', implode(' ', $odd->checkFindings()));

        // A failed call leaves the free result and can be retried.
        SheetReader::$fake = fn () => throw new \RuntimeException('API down');
        $failed = $this->log($rep)->fresh();
        $this->assertSame('consistent', $failed->check_status);
        $this->assertNull($failed->ai_checked_at);
    }

    public function test_without_a_key_only_the_free_checks_run(): void
    {
        Storage::fake('local');
        config(['services.anthropic.sheet_reader' => true, 'services.anthropic.key' => null]);
        $this->assertFalse(SheetReader::enabled());

        $activity = $this->log($this->rep())->fresh();
        $this->assertSame('consistent', $activity->check_status);
        $this->assertNull($activity->ai_checked_at);
    }

    public function test_the_office_verifies_all_consistent_activities_at_once(): void
    {
        Storage::fake('local');
        $this->withoutDefer();
        $admin = User::factory()->admin()->create();
        $rep = $this->rep();
        $good = [$this->log($rep), $this->log($rep, ['title' => 'Tree planting'])];
        $flagged = $this->log($rep, ['evidence' => null]);

        $this->actingAs($admin)->get(route('admin.activities.index'))->assertOk()
            ->assertSee('Verify all 2 marked Verified')->assertSee('No sign-in sheet is attached.')
            ->assertSee('Decision support, not a final verdict.');
        $this->actingAs($admin)->get(route('admin.activities.index', ['check' => 'flagged']))->assertSee($flagged->title)->assertDontSee('Tree planting');

        $this->actingAs($admin)->post(route('admin.activities.verify-consistent'))
            ->assertSessionHas('status', '2 activities marked Verified by the checks were verified, and scores recalculated.');

        foreach ($good as $activity) {
            $this->assertSame('verified', $activity->fresh()->status);
        }
        $this->assertSame('pending', $flagged->fresh()->status);
        $this->assertNotNull($rep->organization->performanceScores()->first());
        $this->assertDatabaseHas('audit_logs', ['action' => 'activity.bulk_verified']);

        $this->actingAs($rep)->post(route('admin.activities.verify-consistent'))->assertForbidden();
    }

    public function test_the_office_can_ask_the_ai_to_read_waiting_sheets(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $activity = $this->log($this->rep());
        $this->actingAs($admin)->post(route('admin.activities.read-sheets'))->assertNotFound(); // switched off

        config(['services.anthropic.sheet_reader' => true]);
        SheetReader::$fake = fn () => ['readable' => true, 'sheet_date' => null, 'sheet_title' => null, 'names_count' => 25];
        $this->actingAs($admin)->get(route('admin.activities.index'))->assertSee('Read 1 new sheet with AI');
        $this->actingAs($admin)->post(route('admin.activities.read-sheets'))->assertSessionHas('status', 'Read 1 sheet.');
        $this->assertSame(25, $activity->fresh()->ai_names_count);
    }
}
