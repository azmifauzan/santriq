<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'classroom_id' => Classroom::factory(),
            'nis' => (string) fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['L', 'P']),
            'birth_date' => fake()->date(),
            'status' => 'active',
        ];
    }
}
