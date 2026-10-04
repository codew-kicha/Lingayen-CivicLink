<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_cso_rep_dashboard_redirects_cso_rep_to_cso_dashboard(): void
    {
        $rep = User::factory()->create();

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertRedirect(route('cso.dashboard'));
    }

    public function test_cso_rep_cannot_access_admin_routes(): void
    {
        $rep = User::factory()->create();

        $response = $this->actingAs($rep)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_cso_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/cso/dashboard');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_role_gated_routes(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }
}
