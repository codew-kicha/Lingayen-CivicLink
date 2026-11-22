<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\LguEvent;
use App\Models\Organization;
use App\Models\User;
use App\Services\ReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dates without a time of day (App\Casts\DateOnly): stored as "2026-11-22" on every database, so a
 * query bounded by a plain date includes that day. The tests run on SQLite, where Laravel's `date`
 * cast used to store "2026-11-22 00:00:00" and such queries left the day out.
 */
class DateHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dates_are_stored_without_a_time(): void
    {
        $activity = Activity::factory()->create(['activity_date' => now()->toDateString()]);
        $event = LguEvent::create(['title' => 'Assembly', 'description' => 'x', 'held_on' => now(), 'created_by' => null]);

        $this->assertSame(now()->toDateString(), DB::table('activities')->where('id', $activity->id)->value('activity_date'));
        $this->assertSame(now()->toDateString(), DB::table('lgu_events')->where('id', $event->id)->value('held_on'));
        // Read back as a Carbon at the start of the day, and a copy changed elsewhere never changes the model.
        $this->assertTrue($activity->fresh()->activity_date->isToday());
        $activity->activity_date->addDays(5);
        $this->assertTrue($activity->activity_date->isToday());
    }

    public function test_the_last_day_of_a_period_counts_in_the_analytics(): void
    {
        Activity::factory()->verified()->create(['activity_date' => now()->toDateString(), 'activity_source' => 'independent']);

        $summary = (new ReportData(now()->subMonth(), now()))->summary();

        $this->assertSame(1, $summary['independent']);
    }

    public function test_an_accreditation_expiring_today_counts_as_expiring(): void
    {
        $organization = Organization::factory()->accredited()->create(['name' => 'Ends Today Circle']);
        $organization->accreditations()->update(['expires_at' => today()->toDateString()]);

        $this->assertSame(1, Accreditation::expiringWithin(90)->count());
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertSee('Ends Today Circle');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.organizations.index', ['status' => 'expiring']))->assertSee('Ends Today Circle');
    }
}
