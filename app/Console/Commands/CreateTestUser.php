<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test
                            {--email= : Email address for the user}
                            {--password= : Password for the user}
                            {--name= : Full name of the user}
                            {--role=user : Role (user, admin, superadmin, analytics)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for the dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║              Create Test User for Dashboard                   ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Get or prompt for details
        $email = $this->option('email') ?: $this->ask('Email address', 'admin@test.com');
        $password = $this->option('password') ?: $this->secret('Password (leave empty for "password")') ?: 'password';
        $name = $this->option('name') ?: $this->ask('Full name', 'Test Admin');
        $role = strtolower((string) ($this->option('role') ?: User::ROLE_USER));

        if (! in_array($role, User::roles(), true)) {
            $this->error('Invalid role. Allowed: '.implode(', ', User::roles()));

            return 1;
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if (! $this->confirm("User with email {$email} already exists. Update it?", true)) {
                $this->error('User creation cancelled.');

                return 1;
            }

            $user = $existingUser;
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->role = $role;
            $user->save();

            $tenant = $user->tenant;
            $this->info('✅ User updated successfully!');
        } else {
            // Get first tenant (or create one)
            $tenant = Tenant::first();

            if (! $tenant) {
                $this->error('⚠️  No tenant found in database!');

                if ($this->confirm('Create a test tenant?', true)) {
                    $tenant = Tenant::create([
                        'name' => 'test_client',
                        'display_name' => 'Test Client',
                        'api_key' => config('backend.default_api_key'),
                    ]);
                    $this->info("✅ Created tenant: {$tenant->name}");
                } else {
                    $this->error('Cannot create user without a tenant.');

                    return 1;
                }
            }

            // Create the user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'tenant_id' => $tenant->id,
                'role' => $role,
            ]);

            $this->info('✅ User created successfully!');
        }

        // Display credentials
        $this->info('');
        $this->line('════════════════════════════════════════════════════════════════');
        $this->line('LOGIN CREDENTIALS');
        $this->line('════════════════════════════════════════════════════════════════');
        $this->line('');
        $this->line('📧 Email:    '.$user->email);
        $this->line('🔑 Password: '.$password);
        $this->line('👤 Name:     '.$user->name);
        $this->line('🎭 Role:     '.$user->role);
        $this->line('🏢 Tenant:   '.($tenant->name ?? 'N/A').' (ID: '.$user->tenant_id.')');
        $this->line('');
        if ($user->isAnalyticsOnly()) {
            $this->line('Access: Analytics + Account only');
        }
        $this->line('════════════════════════════════════════════════════════════════');
        $this->info('');

        $this->info('🌐 Login URL: '.config('app.url').'/login');
        $this->info('');

        return 0;
    }
}
