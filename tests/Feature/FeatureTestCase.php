<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BackendApiFake;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        BackendApiFake::register();
    }

    /**
     * Authenticated user with tenant API key in session (mirrors LoginController).
     */
    protected function actingAsTenantUser(?User $user = null, ?Tenant $tenant = null): User
    {
        if ($user !== null) {
            $tenant = $user->tenant;
        } else {
            $tenant = $tenant ?? Tenant::factory()->create();
            $user = User::factory()->forTenant($tenant)->create();
        }

        $this->actingAs($user);
        $this->withSession([
            'tenant_api_key' => $tenant->api_key,
            'tenant_name' => $tenant->display_name,
        ]);

        return $user;
    }
}
