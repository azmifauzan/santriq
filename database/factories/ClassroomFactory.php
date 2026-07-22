<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Kelas '.fake()->word(),
            'level' => 'Jilid '.fake()->numberBetween(1, 6),
        ];
    }
}
