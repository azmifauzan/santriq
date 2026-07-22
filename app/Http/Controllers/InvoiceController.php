<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramMessage;
use App\Models\Classroom;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Rules\TenantExists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = Invoice::with(['student.classroom', 'payments.verifier'])
            ->latest('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        $invoices = $query->get();
        $classrooms = Classroom::all();
        $students = Student::where('status', 'active')->get();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'classrooms' => $classrooms,
            'students' => $students,
            'filters' => $request->only(['status', 'period']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'student_id' => ['required', TenantExists::in('students')],
            'period' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        $exists = Invoice::where('tenant_id', Auth::user()->tenant_id)
            ->where('student_id', $validated['student_id'])
            ->where('period', $validated['period'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['period' => 'Tagihan untuk santri dan periode ini sudah pernah dibuat.']);
        }

        $invoice = Invoice::create([
            'tenant_id' => Auth::user()->tenant_id,
            'student_id' => $validated['student_id'],
            'period' => $validated['period'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'status' => 'unpaid',
        ]);

        $this->notifyInvoiceCreated($invoice);

        return redirect()->back()->with('success', 'Tagihan SPP berhasil dibuat.');
    }

    public function batchGenerate(Request $request): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'classroom_id' => ['nullable', TenantExists::in('classrooms')],
            'period' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        $query = Student::where('tenant_id', Auth::user()->tenant_id)
            ->where('status', 'active');

        if (! empty($validated['classroom_id'])) {
            $query->where('classroom_id', $validated['classroom_id']);
        }

        $students = $query->get();
        $createdCount = 0;

        DB::transaction(function () use ($students, $validated, &$createdCount) {
            foreach ($students as $student) {
                $exists = Invoice::where('tenant_id', Auth::user()->tenant_id)
                    ->where('student_id', $student->id)
                    ->where('period', $validated['period'])
                    ->exists();

                if (! $exists) {
                    $invoice = Invoice::create([
                        'tenant_id' => Auth::user()->tenant_id,
                        'student_id' => $student->id,
                        'period' => $validated['period'],
                        'amount' => $validated['amount'],
                        'due_date' => $validated['due_date'],
                        'status' => 'unpaid',
                    ]);

                    $this->notifyInvoiceCreated($invoice);
                    $createdCount++;
                }
            }
        });

        return redirect()->back()->with('success', "Berhasil menerbitkan {$createdCount} tagihan SPP.");
    }

    public function verifyPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'in:cash,transfer'],
            'note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'paid_at' => now(),
                'verified_by' => Auth::id(),
                'note' => $validated['note'] ?? null,
            ]);

            $invoice->update(['status' => 'paid']);
        });

        $this->notifyPaymentVerified($invoice);

        return redirect()->back()->with('success', 'Pembayaran SPP terverifikasi & status menjadi LUNAS.');
    }

    private function notifyInvoiceCreated(Invoice $invoice): void
    {
        $student = $invoice->student;
        if (! $student) {
            return;
        }

        $amountFormatted = number_format($invoice->amount, 0, ',', '.');
        $msg = "💳 <b>Tagihan SPP Baru Terbit</b>\n\n".
            "Santri: <b>{$student->name}</b>\n".
            "Periode: <b>{$invoice->period}</b>\n".
            "Jumlah: <b>Rp {$amountFormatted}</b>\n".
            "Jatuh Tempo: <b>{$invoice->due_date}</b>\n\n".
            'Silakan melakukan pembayaran ke pengurus TPA/TPQ.';

        foreach ($student->guardians as $guardian) {
            if (! empty($guardian->telegram_chat_id)) {
                SendTelegramMessage::dispatch($guardian, $msg);
            }
        }
    }

    private function notifyPaymentVerified(Invoice $invoice): void
    {
        $student = $invoice->student;
        if (! $student) {
            return;
        }

        $amountFormatted = number_format($invoice->amount, 0, ',', '.');
        $msg = "🎉 <b>Pembayaran SPP Terverifikasi</b>\n\n".
            "Santri: <b>{$student->name}</b>\n".
            "Periode: <b>{$invoice->period}</b>\n".
            "Jumlah: <b>Rp {$amountFormatted}</b>\n".
            "Status: <b>LUNAS</b>\n\n".
            'Terima kasih atas pembayaran tepat waktu.';

        foreach ($student->guardians as $guardian) {
            if (! empty($guardian->telegram_chat_id)) {
                SendTelegramMessage::dispatch($guardian, $msg);
            }
        }
    }
}
