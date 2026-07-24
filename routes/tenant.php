<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\GuardianAuthController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\GuardianLeaveRequestController;
use App\Http\Controllers\GuardianPortalController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\LembagaController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TenantLandingController;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureStaffTenantMatchesSubdomain;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

$tenantRoutes = function (): void {
    Route::get('/', [TenantLandingController::class, 'show'])->name('tenant.landing');

    // Hands off a login already verified by GoogleAuthController on the apex
    // domain (see GoogleAuthController::redirectToTenantLogin) — the session
    // has to be established here, on the tenant subdomain itself, because
    // SESSION_DOMAIN is deliberately null.
    Route::get('auth/google/verify/{user}', [GoogleAuthController::class, 'verifyLogin'])
        ->middleware('signed')
        ->name('google.login.verify');

    Route::prefix('wali')->name('guardian.')->group(function () {
        Route::get('masuk', [GuardianAuthController::class, 'create'])->name('login');
        Route::post('masuk', [GuardianAuthController::class, 'requestLink'])
            ->middleware('throttle:5,1')
            ->name('login.request');
        Route::post('masuk-demo', [GuardianAuthController::class, 'loginDemo'])->name('login.demo');
        Route::get('masuk/verifikasi/{guardian}', [GuardianAuthController::class, 'verify'])
            ->middleware('signed')
            ->name('login.verify');
        Route::post('keluar', [GuardianAuthController::class, 'logout'])
            ->middleware('auth:guardian')
            ->name('logout');

        Route::middleware('auth:guardian')->prefix('portal')->name('portal.')->group(function () {
            Route::get('/', [GuardianPortalController::class, 'index'])->name('index');
            Route::get('anak/{student}', [GuardianPortalController::class, 'show'])->name('student');
            Route::get('izin', [GuardianLeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::post('izin', [GuardianLeaveRequestController::class, 'store'])->name('leave-requests.store');
        });
    });

    Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class, EnsureOnboardingComplete::class])->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('super-admin/redirect', [SuperAdminController::class, 'redirectHandoff'])->name('super-admin.redirect');
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

    Route::middleware(['auth', EnsureStaffTenantMatchesSubdomain::class])->group(function () {
        Route::redirect('settings', '/settings/profile');
        Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class])->group(function () {
        Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::put('onboarding', [OnboardingController::class, 'update'])->name('onboarding.update');
        Route::post('onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
    });

    Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class, EnsureOnboardingComplete::class])->group(function () {
        Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('settings/security', [SecurityController::class, 'edit'])
            ->middleware(RequirePassword::class)
            ->name('security.edit');

        Route::put('settings/password', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');

        Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

        Route::get('settings/lembaga', [LembagaController::class, 'edit'])->name('lembaga.edit');
        Route::put('settings/lembaga', [LembagaController::class, 'update'])->name('lembaga.update');
    });
};

// {subdomain} is marked optional in both branches purely so Wayfinder emits
// an optional TS argument for pages that call e.g. dashboard() with no tenant
// in scope (the central marketing page) — see resources/js/lib/tenantUrlDefaults.ts.
// It does NOT mean these routes work without a subdomain: a request that
// matches one of these routes with no subdomain segment still can't resolve
// a Tenant, and any controller that needs one 404s via CurrentTenant::get().
if (config('tenancy.subdomain_active')) {
    // Wildcard DNS is live: every lembaga gets {subdomain}.{tenancy.domain}.
    Route::domain('{subdomain?}.'.config('tenancy.domain'))->group($tenantRoutes);
} else {
    // No wildcard DNS yet: serve the same routes as {tenancy.domain}/{subdomain}/...
    // on the apex, which only needs a plain A record. See config/tenancy.php.
    Route::domain(config('tenancy.domain'))->prefix('{subdomain?}')->group($tenantRoutes);
}
