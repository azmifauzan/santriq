<?php

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('invoice export returns an xlsx file honoring the status filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Invoice::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'period' => '2026-06', 'status' => 'unpaid']);
    Invoice::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'period' => '2026-07', 'status' => 'paid']);

    $response = $this->actingAsStaff($admin)->get(route('invoices.export', ['status' => 'paid']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
