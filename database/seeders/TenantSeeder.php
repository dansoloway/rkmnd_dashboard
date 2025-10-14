<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test_client tenant (from your backend)
        $testTenant = Tenant::create([
            'name' => 'test_client',
            'display_name' => 'Test Client',
            'api_key' => 'K388TLiS1qB0lMDVboXbKYQklZzOWVXC', // Will be encrypted automatically
            'plan_type' => 'pro',
            'is_active' => true,
            'settings' => [
                'theme' => 'default',
                'notifications_enabled' => true,
            ],
        ]);

        // Create an admin user for test_client
        User::create([
            'tenant_id' => $testTenant->id,
            'name' => 'Test Admin',
            'email' => 'admin@testclient.com',
            'password' => Hash::make('password'), // Change this in production!
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create a regular user for test_client
        User::create([
            'tenant_id' => $testTenant->id,
            'name' => 'Test User',
            'email' => 'user@testclient.com',
            'password' => Hash::make('password'), // Change this in production!
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Test tenant and users created successfully!');
        $this->command->info('   Admin: admin@testclient.com / password');
        $this->command->info('   User:  user@testclient.com / password');
    }
}
