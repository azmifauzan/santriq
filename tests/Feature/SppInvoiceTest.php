<?php

use App\Jobs\SendTelegramMessage;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('admin can batch generate invoices for active students', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    $student1 = Student::factory()->create(['tenant_id' => $tenant->id]);
    $student2 = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($admin)->post(route('invoices.batch'), [
        'period' => '2026-07',
        'amount' => 50000,
        'due_date' => '2026-07-31',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'tenant_id' => $tenant->id,
        'student_id' => $student1->id,
        'period' => '2026-07',
        'status' => 'unpaid',
    ]);

    $this->assertDatabaseHas('invoices', [
        'tenant_id' => $tenant->id,
        'student_id' => $student2->id,
        'period' => '2026-07',
        'status' => 'unpaid',
    ]);
});

test('duplicate invoice for same student and period is rejected', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'period' => '2026-07',
    ]);

    $response = $this->actingAs($admin)->post(route('invoices.store'), [
        'student_id' => $student->id,
        'period' => '2026-07',
        'amount' => 50000,
        'due_date' => '2026-07-31',
    ]);

    $response->assertSessionHasErrors(['period']);
});

test('admin can verify payment and change status to paid', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id, 'telegram_chat_id' => '12345']);
    $student->guardians()->attach($guardian->id);

    $invoice = Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'amount' => 50000,
        'status' => 'unpaid',
    ]);

    $response = $this->actingAs($admin)->post(route('invoices.verify', $invoice), [
        'amount' => 50000,
        'method' => 'cash',
        'note' => 'Diterima tunai',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => 'paid',
    ]);

    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'amount' => 50000,
        'verified_by' => $admin->id,
    ]);

    Queue::assertPushed(SendTelegramMessage::class);
});
