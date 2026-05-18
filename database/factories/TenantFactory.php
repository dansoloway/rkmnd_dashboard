<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'test_'.Str::lower(Str::random(8));

        return [
            'name' => $name,
            'display_name' => fake()->company(),
            'api_key' => 'test_api_key_'.Str::random(16),
            'plan_type' => 'pro',
            'is_active' => true,
            'settings' => [],
        ];
    }
}
