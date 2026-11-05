<?php

namespace Database\Factories;

use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportCard> */
class ReportCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => Student::factory(),
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'snapshot' => [],
            'teacher_note' => null,
            'published_at' => now(),
        ];
    }
}
