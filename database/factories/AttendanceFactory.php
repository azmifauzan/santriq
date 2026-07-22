<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => Student::factory(),
            'date' => now()->format('Y-m-d'),
            'checked_in_at' => now(),
            'checked_out_at' => null,
            'status' => 'hadir',
            'recorded_by' => User::factory(),
        ];
    }
}
