<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => Student::factory(),
            'period' => now()->format('Y-m'),
            'amount' => 50000,
            'due_date' => now()->endOfMonth()->format('Y-m-d'),
            'status' => 'unpaid',
        ];
    }
}
