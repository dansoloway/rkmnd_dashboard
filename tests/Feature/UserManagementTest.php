<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

class UserManagementTest extends FeatureTestCase
{
    public function test_users_page_displays_manager_role_label(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->forTenant($admin->tenant)->manager()->create([
            'name' => 'Pat Manager',
        ]);

        $this->actingAsTenantUser($admin);

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('Pat Manager')
            ->assertSee('Manager')
            ->assertSee('Manager = full dashboard')
            ->assertDontSee('User = full dashboard');
    }

    public function test_admin_can_email_a_password_reset_link(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $manager = User::factory()->forTenant($admin->tenant)->manager()->create();

        $this->actingAsTenantUser($admin);

        $this->post(route('users.send-reset-link', $manager))
            ->assertRedirect(route('users.edit', $manager))
            ->assertSessionHas('success');

        Notification::assertSentTo($manager, ResetPassword::class);
    }

    public function test_admin_can_impersonate_a_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->forTenant($admin->tenant)->manager()->create();

        $this->actingAsTenantUser($admin);

        $this->post(route('users.impersonate', $manager))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($manager);
        $this->assertEquals($admin->id, session('impersonator_id'));
    }

    public function test_admin_can_stop_impersonating(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->forTenant($admin->tenant)->manager()->create();

        $this->actingAsTenantUser($admin);
        $this->post(route('users.impersonate', $manager));

        $this->post(route('impersonate.stop'))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_manager_cannot_impersonate(): void
    {
        $manager = User::factory()->manager()->create();
        $other = User::factory()->forTenant($manager->tenant)->manager()->create();

        $this->actingAsTenantUser($manager);

        $this->post(route('users.impersonate', $other))
            ->assertForbidden();
    }

    public function test_admin_cannot_impersonate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsTenantUser($admin);

        $this->from(route('users.index'))
            ->post(route('users.impersonate', $admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_admin_cannot_impersonate_user_on_another_tenant(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->manager()->create();

        $this->actingAsTenantUser($admin);

        $this->from(route('users.index'))
            ->post(route('users.impersonate', $outsider))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_impersonate_a_superadmin(): void
    {
        $admin = User::factory()->admin()->create();
        $super = User::factory()->forTenant($admin->tenant)->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $this->actingAsTenantUser($admin);

        $this->from(route('users.index'))
            ->post(route('users.impersonate', $super))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_impersonating_analytics_only_user_can_still_stop(): void
    {
        $admin = User::factory()->admin()->create();
        $analytics = User::factory()->forTenant($admin->tenant)->analytics()->create();

        $this->actingAsTenantUser($admin);

        $this->post(route('users.impersonate', $analytics))
            ->assertRedirect(route('ai-search.analytics'));

        $this->assertAuthenticatedAs($analytics);

        $this->post(route('impersonate.stop'))
            ->assertRedirect(route('users.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_impersonation_banner_is_shown(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Ada Admin']);
        $manager = User::factory()->forTenant($admin->tenant)->manager()->create(['name' => 'Pat Manager']);

        $this->actingAsTenantUser($admin);
        $this->post(route('users.impersonate', $manager));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Viewing as')
            ->assertSee('Pat Manager')
            ->assertSee('Ada Admin')
            ->assertSee('Return to my account');
    }
}
