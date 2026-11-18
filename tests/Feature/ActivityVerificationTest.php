<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use App\Services\SheetReader;
use Database\Seeders\Support\DemoPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Automated verification of activity evidence (pre-final defense panel, requirement 6): up to three
 * photos and a venue per activity; reused or re-saved pictures found by their fingerprint; the
 * photos' camera data (date, editing app, place) compared with the activity; a 0-100 confidence
 * score giving Verified, Needs review or Flagged with reasons; decision support only.
 */
class ActivityVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** Lingayen's town proper, and Manila (about 170 km away). */
    private const LINGAYEN = ['lat' => 16.0301, 'lng' => 120.2254];

    private const MANILA = ['lat' => 14.5995, 'lng' => 120.9842];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        SheetReader::$fake = null;
        parent::tearDown();
    }

    private function rep(): User
    {
        return Organization::factory()->accredited()->create()->user;
    }

    /** A phone photo of the activity: taken that day, in Lingayen, unless $exif says otherwise. */
    private function photo(string $name = 'IMG_0001.jpg', array $exif = [], int $seed = 1, ?string $source = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, DemoPhoto::jpeg($source, $exif, seed: $seed));
    }

    private function onTheDay(array $extra = []): array
    {
        return ['taken_at' => now()->subDays(3)->format('Y-m-d').' 09:30:00', 'make' => 'samsung', 'model' => 'SM-A155F'] + self::LINGAYEN + $extra;
    }

    private function log(User $rep, array $overrides = []): Activity
    {
        $this->actingAs($rep)->post(route('cso.activities.store'), $overrides + [
            'title' => 'Coastal clean-up',
            'description' => 'Members cleared the shoreline.',
            'activity_date' => now()->subDays(3)->toDateString(),
            'participants_estimate' => 20,
            'activity_source' => 'independent',
            'evidence' => UploadedFile::fake()->createWithContent('sheet.pdf', 'sheet-'.uniqid()),
        ])->assertSessionHasNoErrors();

        return Activity::latest('id')->firstOrFail();
    }

    public function test_original_photos_from_the_day_in_lingayen_are_verified_with_reasons(): void
    {
        $activity = $this->log($this->rep(), [
            'venue' => 'Barangay Hall, Libsong East',
            'photos' => [$this->photo('a.jpg', $this->onTheDay(), 1), $this->photo('b.jpg', $this->onTheDay(), 2)],
        ]);

        $this->assertSame(['consistent', 100, 'Verified'], [$activity->check_status, $activity->check_score, $activity->checkLabel()]);
        $this->assertSame([], $activity->checkFindings());
        $this->assertContains('Photo 1 was taken on the activity date.', $activity->checkConfirmations());
        $this->assertContains('Photo 2 was taken in Lingayen (GPS).', $activity->checkConfirmations());

        $photo = $activity->photos()->first();
        $this->assertSame(['samsung SM-A155F', 16.0301], [$photo->metadata['camera'], $photo->metadata['lat']]);
        $this->assertSame(16, strlen($photo->phash));
        $this->assertSame('Barangay Hall, Libsong East', $activity->venue);
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_a_reused_or_resaved_picture_is_flagged(): void
    {
        $source = public_path('images/events/cso1.jpg');
        $first = $this->log($this->rep(), ['title' => 'Tree planting', 'photos' => [
            UploadedFile::fake()->createWithContent('original.jpg', DemoPhoto::jpeg($source, $this->onTheDay())),
        ]]);
        $this->assertSame('consistent', $first->check_status);

        // Another organization, the same picture made smaller and recompressed (as a chat app would).
        $copy = $this->log($this->rep(), ['photos' => [
            UploadedFile::fake()->createWithContent('copy.jpg', DemoPhoto::jpeg($source, [], width: 400, quality: 55)),
        ]]);

        $this->assertSame('flagged', $copy->check_status);
        $this->assertStringContainsString('Photo 1 looks like a picture already attached to “Tree planting”', $copy->checkFindings()[0]);
        $this->assertLessThan(50, $copy->check_score);
    }

    public function test_edited_off_date_or_far_away_photos_need_review(): void
    {
        $rep = $this->rep();

        $edited = $this->log($rep, ['photos' => [$this->photo('poster.jpg', $this->onTheDay(['software' => 'Canva']), 11)]]);
        $this->assertSame(['review', 75], [$edited->check_status, $edited->check_score]);
        $this->assertContains('Photo 1 was edited in Canva.', $edited->checkFindings());

        $old = $this->log($rep, ['photos' => [$this->photo('old.jpg', ['taken_at' => now()->subDays(40)->format('Y-m-d').' 10:00:00'] + self::LINGAYEN, 12)]]);
        $this->assertContains('Photo 1 was taken on '.now()->subDays(40)->format('d M Y').', 37 days from the activity date.', $old->checkFindings());

        $far = $this->log($rep, ['photos' => [$this->photo('far.jpg', ['taken_at' => now()->subDays(3)->format('Y-m-d').' 10:00:00'] + self::MANILA, 13)]]);
        $this->assertMatchesRegularExpression('/^Photo 1 was taken 1\d\d km from Lingayen\.$/', $far->checkFindings()[0]);
        $this->assertSame('consistent', $far->check_status);   // 85: one medium point alone stays Verified

        // Both an editing app and a date weeks off: Needs review.
        $both = $this->log($rep, ['photos' => [$this->photo('both.jpg', ['software' => 'Adobe Photoshop 25.0', 'taken_at' => now()->subDays(40)->format('Y-m-d').' 10:00:00'], 14)]]);
        $this->assertSame(['review', 55], [$both->check_status, $both->check_score]);

        // A photo passed through a chat app has no camera data: a small point, not a flag.
        $chat = $this->log($rep, ['photos' => [$this->photo('chat.jpg', [], 9)]]);
        $this->assertContains('Photo 1 has no camera data (date or location), as when a photo is sent through a chat app.', $chat->checkFindings());
        $this->assertSame(['consistent', 95], [$chat->check_status, $chat->check_score]);
    }

    public function test_the_venue_is_checked_against_a_typed_sheet(): void
    {
        $rep = $this->rep();
        $date = now()->subDays(3)->format('F j, Y');

        $named = $this->log($rep, ['venue' => 'Libsong Covered Court', 'evidence_text' => "ATTENDANCE SHEET. Coastal clean-up. {$date}. Venue: Libsong covered court. Names: Juan, Maria."]);
        $this->assertContains('The sheet mentions the venue.', $named->checkConfirmations());

        $elsewhere = $this->log($rep, ['venue' => 'Libsong Covered Court', 'evidence_text' => "ATTENDANCE SHEET. Coastal clean-up. {$date}. Venue: Municipal gymnasium. Names: Juan, Maria."]);
        $this->assertContains("The sheet doesn't mention the venue (Libsong Covered Court).", $elsewhere->checkFindings());
    }

    public function test_at_most_three_photos_counting_the_ones_kept(): void
    {
        $rep = $this->rep();
        $base = ['title' => 'Coastal clean-up', 'description' => 'Members cleared the shoreline.', 'activity_date' => now()->subDays(3)->toDateString(), 'activity_source' => 'independent'];

        $this->actingAs($rep)->post(route('cso.activities.store'), $base + ['photos' => array_map(fn ($i) => $this->photo("p{$i}.jpg", [], $i), range(1, 4))])
            ->assertSessionHasErrors('photos');
        $this->actingAs($rep)->post(route('cso.activities.store'), $base + ['photos' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('photos.0');

        $activity = $this->log($rep, ['photos' => [$this->photo('a.jpg', [], 1), $this->photo('b.jpg', [], 2)]]);
        $update = fn (array $extra) => $this->actingAs($rep)->put(route('cso.activities.update', $activity), $base + $extra);

        $update(['photos' => [$this->photo('c.jpg', [], 3), $this->photo('d.jpg', [], 4)]])->assertSessionHasErrors('photos');
        $removed = $activity->photos()->first();
        $update(['remove_photos' => [$removed->id], 'photos' => [$this->photo('c.jpg', [], 3), $this->photo('d.jpg', [], 4)]])->assertSessionHasNoErrors();

        $this->assertSame(3, $activity->photos()->count());
        $this->assertNull($activity->photos()->find($removed->id));
        Storage::disk('local')->assertMissing($removed->path);
        $this->assertNotNull($activity->fresh()->check_status);   // checked again
    }

    public function test_photos_are_private_to_the_organization_and_the_office(): void
    {
        $activity = $this->log($this->rep(), ['photos' => [$this->photo()]]);
        $photo = $activity->photos()->first();
        $url = route('activities.photo', [$activity, $photo]);

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($activity->organization->user)->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
        $this->actingAs($this->rep())->get($url)->assertForbidden();

        // A photo id under another activity is not found.
        $other = $this->log($this->rep(), ['photos' => [$this->photo('x.jpg', [], 5)]]);
        $this->actingAs($activity->organization->user)->get(route('activities.photo', [$activity, $other->photos()->first()]))->assertNotFound();

        // Withdrawing removes the files.
        $this->actingAs($activity->organization->user)->delete(route('cso.activities.destroy', $activity));
        Storage::disk('local')->assertMissing($photo->path);
    }

    public function test_the_office_sees_the_result_and_bulk_verifies_only_verified_ones(): void
    {
        $this->withoutDefer();
        $admin = User::factory()->admin()->create();
        $rep = $this->rep();
        $verified = $this->log($rep, ['title' => 'Tree planting', 'photos' => [$this->photo('a.jpg', $this->onTheDay())]]);
        $review = $this->log($rep, ['title' => 'Feeding program', 'photos' => [$this->photo('b.jpg', ['software' => 'Canva', 'taken_at' => now()->subDays(40)->format('Y-m-d').' 10:00:00'], 2)]]);
        $this->assertSame(['consistent', 'review'], [$verified->check_status, $review->check_status]);

        $this->actingAs($admin)->get(route('admin.activities.index'))->assertOk()
            ->assertSee('Decision support, not a final verdict.')
            ->assertSee('Needs review')->assertSee('55/100')->assertSee('Photo 1 was edited in Canva.')
            ->assertSee('Verify all 1 marked Verified')
            // The review dialog carries each photo with what its camera data says.
            ->assertSee('Taken '.now()->subDays(3)->format('d M Y').', 9:30 AM', false)->assertSee('samsung SM-A155F', false);
        $this->actingAs($admin)->get(route('admin.activities.index', ['check' => 'review']))->assertSee('Feeding program')->assertDontSee('Tree planting');

        $this->actingAs($admin)->post(route('admin.activities.verify-consistent'))
            ->assertSessionHas('status', '1 activity marked Verified by the checks was verified, and scores recalculated.');
        $this->assertSame(['verified', 'pending'], [$verified->fresh()->status, $review->fresh()->status]);
    }

    public function test_the_ai_reading_looks_at_the_photos_and_the_venue(): void
    {
        $this->withoutDefer();
        config(['services.anthropic.sheet_reader' => true]);
        $rep = $this->rep();

        SheetReader::$fake = fn () => ['readable' => true, 'sheet_date' => now()->subDays(3)->toDateString(), 'sheet_title' => 'Coastal clean-up',
            'sheet_venue' => 'Municipal gymnasium', 'names_count' => 18, 'photos_match' => false, 'photos_note' => 'The photos show an empty office.'];
        $odd = $this->log($rep, ['venue' => 'Libsong Covered Court', 'photos' => [$this->photo('a.jpg', $this->onTheDay())]])->fresh();

        $this->assertContains("The AI found the photos don't show this activity: The photos show an empty office.", $odd->checkFindings());
        $this->assertContains('The sheet names the venue as “Municipal gymnasium”; the activity was logged at Libsong Covered Court.', $odd->checkFindings());
        $this->assertContains('The date on the sheet matches the activity.', $odd->checkConfirmations());
        $this->assertSame(['review', 70], [$odd->check_status, $odd->check_score]);

        SheetReader::$fake = fn () => ['readable' => true, 'sheet_date' => now()->subDays(3)->toDateString(), 'sheet_title' => null,
            'sheet_venue' => null, 'names_count' => 18, 'photos_match' => true, 'photos_note' => 'Volunteers collecting litter on a beach.'];
        $fine = $this->log($rep, ['photos' => [$this->photo('b.jpg', $this->onTheDay(), 2)]])->fresh();
        $this->assertContains('The AI found the photos consistent with the activity: Volunteers collecting litter on a beach.', $fine->checkConfirmations());
        $this->assertSame('consistent', $fine->check_status);
    }

    public function test_the_checks_can_be_rerun_after_an_update(): void
    {
        $activity = $this->log($this->rep(), ['photos' => [$this->photo('a.jpg', $this->onTheDay())]]);
        $activity->forceFill(['check_status' => null, 'check_score' => null, 'check_findings' => ['free' => ['An old finding.']]])->save();

        $this->artisan('activities:recheck')->expectsOutput('Checked 1 waiting activity.')->assertSuccessful();

        $this->assertSame(['consistent', 100], [$activity->fresh()->check_status, $activity->fresh()->check_score]);
        $this->assertNotContains('An old finding.', $activity->fresh()->checkFindings());
    }
}
