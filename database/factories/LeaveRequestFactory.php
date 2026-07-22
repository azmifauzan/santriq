<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = now()->format('Y-m-d');

        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => Student::factory(),
            'type' => fake()->randomElement(['sakit', 'izin']),
            'start_date' => $start,
            'end_date' => $start,
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }
}
