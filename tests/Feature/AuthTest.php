<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends FeatureTestCase
{
    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_can_view_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@test.example',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('login'), [
            'email' => 'login@test.example',
            'password' => 'secret-password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertEquals($user->tenant->api_key, session('tenant_api_key'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@test.example',
            'password' => Hash::make('secret-password'),
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'login@test.example',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->actingAsTenantUser();

        $this->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
