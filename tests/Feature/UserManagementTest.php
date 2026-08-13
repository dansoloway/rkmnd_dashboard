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
}
