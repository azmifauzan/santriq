<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('telegram.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('teachers', TeacherController::class)->except(['create', 'edit', 'show']);
    Route::resource('classrooms', ClassroomController::class)->except(['create', 'edit', 'show']);

    Route::get('students/print-cards', [StudentController::class, 'printCards'])->name('students.print-cards');
    Route::resource('students', StudentController::class)->except(['create', 'edit', 'show']);

    Route::resource('guardians', GuardianController::class)->except(['create', 'edit', 'show']);

    Route::get('scan', [AttendanceController::class, 'scanPage'])->name('attendance.scan-page');
    Route::post('attendance/scan', [AttendanceController::class, 'scan'])->name('attendance.scan')->middleware('throttle:60,1');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');

    Route::resource('achievements', AchievementController::class)->except(['create', 'edit', 'show']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('invoices/batch', [InvoiceController::class, 'batchGenerate'])->name('invoices.batch');
    Route::post('invoices/{invoice}/verify', [InvoiceController::class, 'verifyPayment'])->name('invoices.verify');

    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::put('leave-requests/{leaveRequest}/review', [LeaveRequestController::class, 'review'])->name('leave-requests.review');
});

require __DIR__.'/settings.php';
