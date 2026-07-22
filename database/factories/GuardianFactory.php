<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'telegram_chat_id' => null,
        ];
    }
}
