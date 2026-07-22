<?php

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => Student::factory(),
            'category' => fake()->randomElement(['Hafalan Qur\'an', 'Iqra', 'Hadits', 'Akhlak']),
            'title' => 'Surah '.fake()->word(),
            'note' => fake()->sentence(),
            'score' => fake()->numberBetween(70, 100),
            'achieved_at' => now()->format('Y-m-d'),
            'recorded_by' => User::factory(),
        ];
    }
}
