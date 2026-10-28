<?php

namespace Tests\Feature;

use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The Desk Officer sets an accreditation's expiry date when approving and can change it later. */
class AccreditationExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_uses_the_chosen_expiry_date_or_three_years(): void
    {
        $admin = User::factory()->admin()->create();
        $chosen = ApplicationModel::factory()->create(['status' => 'under_review', 'sb_stage' => 'endorsed']);
        $default = ApplicationModel::factory()->create(['status' => 'under_review', 'sb_stage' => 'endorsed']);

        $this->actingAs($admin)->get(route('admin.applications.show', $chosen))->assertSee('Accreditation valid until');

        $this->actingAs($admin)->post(route('admin.applications.approve', $chosen), ['expires_at' => now()->addYears(2)->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(now()->addYears(2)->toDateString(), $chosen->fresh()->accreditation->expires_at->toDateString());

        $this->actingAs($admin)->post(route('admin.applications.approve', $default))->assertSessionHasNoErrors();
        $this->assertSame(now()->addYears(3)->toDateString(), $default->fresh()->accreditation->expires_at->toDateString());
    }

    public function test_approval_refuses_a_past_or_far_future_date(): void
    {
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->create(['status' => 'under_review', 'sb_stage' => 'endorsed']);

        $this->actingAs($admin)->post(route('admin.applications.approve', $application), ['expires_at' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('expires_at');
        $this->actingAs($admin)->post(route('admin.applications.approve', $application), ['expires_at' => now()->addYears(11)->toDateString()])
            ->assertSessionHasErrors('expires_at');
        $this->assertDatabaseCount('accreditations', 0);
    }

    public function test_the_expiry_date_can_be_changed_and_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->accredited()->create();
        $old = $organization->accreditations()->value('expires_at');
        $new = now()->addMonths(18)->toDateString();

        $this->actingAs($admin)->get(route('admin.organizations.edit', $organization))->assertSee('Change expiry date');

        $this->actingAs($admin)->patch(route('admin.organizations.expiry', $organization), ['expires_at' => $new, 'expiry_reason' => 'Extended by SB Resolution No. 2026-41'])
            ->assertSessionHas('status');
        $this->assertSame($new, $organization->accreditations()->first()->expires_at->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'accreditation.expiry_changed', 'subject_id' => $organization->id]);
        $this->assertStringContainsString('2026-41', \App\Models\AuditLog::where('action', 'accreditation.expiry_changed')->first()->meta['reason']);
        $this->assertNotSame($old, $new);
    }

    public function test_a_past_date_is_refused_and_only_admins_with_an_active_accreditation_can_change_it(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->accredited()->create();

        $this->actingAs($admin)->patch(route('admin.organizations.expiry', $organization), ['expires_at' => now()->toDateString()])
            ->assertSessionHasErrors(['expires_at' => 'Choose a date after today. To end the accreditation now, revoke it instead.']);

        $notAccredited = Organization::factory()->create();
        $this->actingAs($admin)->patch(route('admin.organizations.expiry', $notAccredited), ['expires_at' => now()->addYear()->toDateString()])->assertNotFound();

        $rep = User::factory()->create();
        $this->actingAs($rep)->patch(route('admin.organizations.expiry', $organization), ['expires_at' => now()->addYear()->toDateString()])->assertForbidden();
    }
}
