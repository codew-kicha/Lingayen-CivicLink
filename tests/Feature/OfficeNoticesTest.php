<?php

namespace Tests\Feature;

use App\Models\OfficeNotice;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OfficeNoticeSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Notices from the Desk Office to organizations (pre-final defense panel). */
class OfficeNoticesTest extends TestCase
{
    use RefreshDatabase;

    private function orgWithRep(array $attributes = [], bool $accredited = false): User
    {
        $factory = Organization::factory();
        $organization = ($accredited ? $factory->accredited() : $factory)->create($attributes);

        return $organization->user;
    }

    public function test_an_audience_reaches_the_right_representatives(): void
    {
        $toda = $this->orgWithRep(['sector' => 'TODA', 'barangay' => 'Baay'], accredited: true);
        $health = $this->orgWithRep(['sector' => 'Health', 'barangay' => 'Baay']);
        $deactivated = $this->orgWithRep(['sector' => 'TODA']);
        $deactivated->forceFill(['is_active' => false])->save();
        Organization::factory()->create(['user_id' => null, 'sector' => 'TODA']); // paper only, no account

        $ids = fn (string $audience, ?string $value = null) => OfficeNotice::recipients($audience, $value)->pluck('id')->sort()->values()->all();

        $this->assertSame([$toda->id], $ids('sector', 'TODA'));
        $this->assertSame(collect([$toda->id, $health->id])->sort()->values()->all(), $ids('barangay', 'Baay'));
        $this->assertSame([$toda->id], $ids('accredited'));
        $this->assertSame([$health->id], $ids('organization', (string) $health->organization->id));
        $this->assertCount(2, $ids('all'));
    }

    public function test_the_office_sends_a_notice_and_representatives_see_it_with_an_unread_badge(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $toda = $this->orgWithRep(['sector' => 'TODA']);
        $other = $this->orgWithRep(['sector' => 'Health']);

        $this->actingAs($admin)->get(route('admin.notices.count', ['audience' => 'sector', 'audience_value' => 'TODA']))
            ->assertOk()->assertJson(['recipients' => 1]);

        $this->actingAs($admin)->post(route('admin.notices.store'), [
            'audience' => 'sector', 'audience_value' => 'TODA', 'title' => 'TODA road safety seminar', 'body' => "Saturday, 9 AM.\n\nBring your IDs.",
        ])->assertSessionHas('status', 'Notice sent to 1 organization.');

        Notification::assertSentTo($toda, OfficeNoticeSent::class, fn ($n, $channels) => $channels === ['database']);
        Notification::assertNotSentTo($other, OfficeNoticeSent::class);
        $this->assertDatabaseHas('office_notices', ['title' => 'TODA road safety seminar', 'recipients' => 1, 'emailed' => false]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'notice.sent']);
        $this->actingAs($admin)->get(route('admin.notices.index'))->assertSee('TODA road safety seminar')->assertSee('TODA sector');
    }

    public function test_the_representative_reads_it_under_notifications(): void
    {
        $admin = User::factory()->admin()->create();
        $rep = $this->orgWithRep();

        $this->actingAs($admin)->post(route('admin.notices.store'), [
            'audience' => 'all', 'title' => 'CSO Assembly on 15 December', 'body' => 'All accredited CSOs are invited.',
        ]);

        // The unread count shows in the sidebar until the notifications are read.
        $this->actingAs($rep)->get(route('cso.profile.edit'))->assertSee('data-nav-count="unreadNotifications"', false);
        $this->actingAs($rep)->get(route('cso.notifications.index'))->assertOk()
            ->assertSee('CSO Assembly on 15 December')->assertSee('From the Civil Society Desk Office')->assertSee('New');
        $this->assertSame(0, $rep->unreadNotifications()->count());
    }

    public function test_also_email_sends_after_the_response_to_confirmed_addresses(): void
    {
        Notification::fake();
        $this->withoutDefer();
        $admin = User::factory()->admin()->create();
        $confirmed = $this->orgWithRep(['sector' => 'OFW']);
        $unconfirmed = $this->orgWithRep(['sector' => 'OFW']);
        $unconfirmed->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($admin)->post(route('admin.notices.store'), [
            'audience' => 'sector', 'audience_value' => 'OFW', 'title' => 'Reminder', 'body' => 'Submit your reports.', 'email' => '1',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($confirmed, OfficeNoticeSent::class, fn ($n, $channels) => $channels === ['mail']);
        Notification::assertNotSentTo($unconfirmed, OfficeNoticeSent::class, fn ($n, $channels) => $channels === ['mail']);
        Notification::assertSentTo($unconfirmed, OfficeNoticeSent::class, fn ($n, $channels) => $channels === ['database']);
    }

    public function test_an_empty_audience_and_representatives_are_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $rep = $this->orgWithRep();

        $paperOnly = Organization::factory()->create(['user_id' => null]);
        $this->actingAs($admin)->post(route('admin.notices.store'), ['audience' => 'organization', 'audience_value' => (string) $paperOnly->id, 'title' => 'X', 'body' => 'Y'])
            ->assertSessionHasErrors('audience');
        $this->actingAs($admin)->post(route('admin.notices.store'), ['audience' => 'sector', 'audience_value' => 'Not a sector', 'title' => 'X', 'body' => 'Y'])
            ->assertSessionHasErrors('audience_value');
        $this->actingAs($rep)->post(route('admin.notices.store'), ['audience' => 'all', 'title' => 'X', 'body' => 'Y'])->assertForbidden();
        $this->actingAs($rep)->get(route('admin.notices.index'))->assertForbidden();
    }

    public function test_the_organization_page_can_send_to_that_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $rep = $this->orgWithRep();

        $this->actingAs($admin)->get(route('admin.organizations.edit', $rep->organization))->assertOk()
            ->assertSee('Send a notice')->assertSee('To '.$rep->organization->name, false);
    }
}
