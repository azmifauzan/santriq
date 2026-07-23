# Landing Page & Login Wali per Subdomain — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every lembaga a permanent subdomain (`{subdomain}.santriq.web.id`) that hosts a landing page with admin-editable content, its own staff login/dashboard, and a password-less guardian portal reached via a Telegram magic-link.

**Architecture:** A tolerant `ResolveTenantFromDomain` middleware runs on every request, binding the matching `Tenant` into the container and registering `URL::defaults(['subdomain' => ...])` whenever the host matches `{subdomain}.{tenant_domain}`; it no-ops on the bare apex domain. All existing staff routes move from `routes/web.php` into a new `routes/tenant.php`, wrapped in `Route::domain('{subdomain}.'.config('tenancy.domain'))`. The apex domain keeps only the marketing page, registration, the subdomain-availability check, and the Telegram webhook, wrapped in `Route::domain(config('tenancy.domain'))`. A new `guardian` auth guard (session, no password) lets wali log in via a 15-minute signed link delivered over the existing `SendTelegramMessage` job.

**Tech Stack:** Laravel 13, Fortify v1, Inertia v3 + Vue 3, Pest v4, Pint, Larastan level 7, Tailwind v4, Wayfinder.

## Global Constraints

- PHP 8.5: curly braces always, constructor property promotion, explicit return types on every method.
- Foreign keys coming from a request use `App\Rules\TenantExists::in()`, never Laravel's plain `exists:` rule (`docs/RENCANA-IMPLEMENTASI.md` § 1) — except guardian-portal endpoints, which authenticate under the `guardian` guard and therefore verify ownership via the `guardian_student` pivot instead (see Task 12).
- Never call `withoutGlobalScopes()` on a user-facing request path.
- Every task that touches Vue/Inertia code: activate the `inertia-vue-development` skill first. Every task that adds/edits a Fortify action, view binding, or auth flow: activate `fortify-development` first. Every task that writes or edits a Pest test: activate `pest-testing` first. Every task that calls a backend route from the frontend: use Wayfinder-generated functions from `@/routes` / `@/actions` (activate `wayfinder-development`), never a hardcoded URL string.
- After any PHP file change: `vendor/bin/pint --dirty --format agent`.
- Run only the tests relevant to the task being worked (`php artisan test --compact --filter=...`); the final task runs the full `composer test` suite.
- Commit after each task's tests pass. Do not batch multiple tasks into one commit.

---

### Task 1: Tenant-domain config, resolver middleware, and the `CurrentTenant` helper

**Files:**
- Create: `config/tenancy.php`
- Modify: `.env.example`
- Modify: `phpunit.xml`
- Create: `app/Support/CurrentTenant.php`
- Create: `app/Http/Middleware/ResolveTenantFromDomain.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/ResolveTenantFromDomainTest.php`

**Interfaces:**
- Produces: `config('tenancy.domain')` — string, the root tenant domain (e.g. `santriq.test` in tests).
- Produces: `App\Support\CurrentTenant::get(): App\Models\Tenant` — throws if no tenant is bound (only call it from code that only runs behind `ResolveTenantFromDomain` on a matched subdomain).
- Produces: `App\Support\CurrentTenant::resolved(): bool` — true once the middleware has bound a tenant for this request.
- Produces: `App\Http\Middleware\ResolveTenantFromDomain` — appended to the global `web` middleware group; binds `Tenant::class` into the container and calls `URL::defaults(['subdomain' => ...])` when the request host is `{label}.{tenant_domain}` and a `Tenant` with that `subdomain` exists; 404s only when the host *matches the subdomain shape* but no such tenant exists; otherwise no-ops (bare apex, unrelated host, `www`, etc. pass through untouched).

- [ ] **Step 1: Create the tenancy config**

```php
<?php

// config/tenancy.php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Root Domain
    |--------------------------------------------------------------------------
    |
    | Every lembaga is served from {subdomain}.{domain}. The apex domain
    | itself only serves the marketing page, registration, and the Telegram
    | webhook (see routes/web.php and routes/tenant.php).
    |
    */
    'domain' => env('APP_TENANT_DOMAIN', 'santriq.web.id'),
];
```

- [ ] **Step 2: Add the env var**

In `.env.example`, right after `APP_URL=http://localhost`:

```
APP_URL=http://localhost
APP_TENANT_DOMAIN=santriq.web.id
```

In `phpunit.xml`, inside the existing `<php>` block, add a fixed value so tests never depend on a developer's local `.env`:

```xml
<env name="APP_TENANT_DOMAIN" value="santriq.test"/>
```

- [ ] **Step 3: Write the failing test**

```php
<?php

// tests/Feature/ResolveTenantFromDomainTest.php

use App\Models\Tenant;

test('unknown subdomain returns 404', function () {
    $this->get('http://ghost.santriq.test/')->assertNotFound();
});

test('bare apex domain is left untouched', function () {
    $this->get('http://santriq.test/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});

test('known subdomain resolves the matching tenant', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-cek']);

    $this->get("http://{$tenant->subdomain}.santriq.test/")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tenant/Landing')
            ->where('tenant.id', $tenant->id)
        );
});
```

- [ ] **Step 4: Run to verify it fails**

Run: `php artisan test --compact --filter=ResolveTenantFromDomainTest`
Expected: FAIL — `Route::domain(config('tenancy.domain'))` group and the `Tenant/Landing` route don't exist yet (this test is written now, will pass once Task 1 + Task 4 land; for now just confirm it fails for the *expected* reason: no matching route / 404 everywhere).

- [ ] **Step 5: Create the `CurrentTenant` support class**

```php
<?php

namespace App\Support;

use App\Models\Tenant;
use RuntimeException;

class CurrentTenant
{
    public static function get(): Tenant
    {
        if (! app()->bound(Tenant::class)) {
            throw new RuntimeException('No tenant has been resolved for this request.');
        }

        return app(Tenant::class);
    }

    public static function resolved(): bool
    {
        return app()->bound(Tenant::class);
    }
}
```

- [ ] **Step 6: Create the middleware**

```php
<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $suffix = '.'.config('tenancy.domain');
        $host = $request->getHost();

        if (! str_ends_with($host, $suffix)) {
            return $next($request);
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        abort_unless($tenant !== null, 404);

        app()->instance(Tenant::class, $tenant);
        URL::defaults(['subdomain' => $subdomain]);

        return $next($request);
    }
}
```

- [ ] **Step 7: Register the middleware globally**

In `bootstrap/app.php`, add the import and append to the `web` group:

```php
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenantFromDomain;
```

```php
        $middleware->web(append: [
            ResolveTenantFromDomain::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
```

- [ ] **Step 8: Run to verify it still fails the same way (routes not moved yet)**

Run: `php artisan test --compact --filter=ResolveTenantFromDomainTest`
Expected: `unknown subdomain returns 404` PASSES already (middleware 404s). `bare apex domain is left untouched` and `known subdomain resolves the matching tenant` still FAIL (no `Tenant/Landing` route, no `subdomain` column yet) — expected at this point in the plan, will go green after Task 2 and Task 6.

- [ ] **Step 9: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add config/tenancy.php .env.example phpunit.xml app/Support/CurrentTenant.php app/Http/Middleware/ResolveTenantFromDomain.php bootstrap/app.php tests/Feature/ResolveTenantFromDomainTest.php
git commit -m "feat: resolve tenant from subdomain on every request"
```

---

### Task 2: Rename `tenants.slug` to `tenants.subdomain`

**Files:**
- Create: `database/migrations/2026_07_23_100000_rename_slug_to_subdomain_on_tenants_table.php`
- Modify: `app/Models/Tenant.php`
- Modify: `database/factories/TenantFactory.php`
- Modify: `database/seeders/DatabaseSeeder.php:15` (the `['slug' => 'tpq-demo']` lookup)
- Test: `tests/Feature/ResolveTenantFromDomainTest.php` (already written in Task 1 — will go green here)

**Interfaces:**
- Produces: `Tenant::$subdomain` (string, unique) — replaces `Tenant::$slug` everywhere.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->renameColumn('slug', 'subdomain');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->renameColumn('subdomain', 'slug');
        });
    }
};
```

- [ ] **Step 2: Update the model**

In `app/Models/Tenant.php`, replace every `slug` reference:

```php
/**
 * @property int $id
 * @property string $name
 * @property string $subdomain
 * @property string|null $address
 * @property string|null $phone
 * @property string $timezone
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'subdomain', 'address', 'phone', 'timezone', 'settings'])]
class Tenant extends Model
```

- [ ] **Step 3: Update the factory**

```php
    public function definition(): array
    {
        $name = fake()->company().' TPA';

        return [
            'name' => $name,
            'subdomain' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'timezone' => 'Asia/Jakarta',
            'settings' => [],
        ];
    }
```

- [ ] **Step 4: Update the seeder**

In `database/seeders/DatabaseSeeder.php:15`, change `['slug' => 'tpq-demo']` to `['subdomain' => 'tpq-demo']`.

- [ ] **Step 5: Run migrations and the Task 1 test**

Run: `php artisan migrate:fresh`
Expected: migrates cleanly.

Run: `php artisan test --compact --filter=ResolveTenantFromDomainTest`
Expected: all 3 PASS now.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_07_23_100000_rename_slug_to_subdomain_on_tenants_table.php app/Models/Tenant.php database/factories/TenantFactory.php database/seeders/DatabaseSeeder.php
git commit -m "refactor: rename tenants.slug to tenants.subdomain"
```

---

### Task 3: Route restructuring — apex vs. per-tenant domain groups

**Files:**
- Modify: `routes/web.php`
- Create: `routes/tenant.php`
- Delete: `routes/settings.php` (folded into `routes/tenant.php`)
- Create: `app/Http/Middleware/EnsureStaffTenantMatchesSubdomain.php`
- Test: `tests/Feature/StaffTenantSubdomainGuardTest.php`

**Interfaces:**
- Consumes: `App\Support\CurrentTenant::get()` (Task 1), `Tenant::$subdomain` (Task 2).
- Produces: every existing staff route name (`dashboard`, `teachers.*`, `classrooms.*`, `students.*`, `guardians.*`, `attendance.*`, `achievements.*`, `reports.*`, `invoices.*`, `leave-requests.*`, `profile.*`, `security.*`, `appearance.edit`) now requires a `subdomain` route parameter (or relies on `URL::defaults`).

Per Laravel 13's routing changelog, domain-constrained routes are matched before non-domain routes regardless of registration order, so wrapping the apex group is about intent and isolation, not about avoiding shadowing.

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Feature/StaffTenantSubdomainGuardTest.php

use App\Models\Tenant;
use App\Models\User;

test('a staff session for tenant A is logged out when it hits tenant B subdomain', function () {
    $tenantA = Tenant::factory()->create(['subdomain' => 'lembaga-a']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'lembaga-b']);
    $adminA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);

    $response = $this->actingAs($adminA)
        ->get("http://{$tenantB->subdomain}.santriq.test/dashboard");

    $response->assertRedirect("http://{$tenantB->subdomain}.santriq.test/login");
    $this->assertGuest();
});

test('a staff session for its own tenant subdomain is not disturbed', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'lembaga-c']);
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAs($admin)
        ->get("http://{$tenant->subdomain}.santriq.test/dashboard")
        ->assertOk();

    $this->assertAuthenticated();
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=StaffTenantSubdomainGuardTest`
Expected: FAIL — `/dashboard` isn't domain-scoped yet, `EnsureStaffTenantMatchesSubdomain` doesn't exist.

- [ ] **Step 3: Write the middleware**

```php
<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffTenantMatchesSubdomain
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user !== null && $user->tenant_id !== null && $user->tenant_id !== CurrentTenant::get()->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Rewrite `routes/web.php`**

```php
<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TenantSubdomainAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::domain(config('tenancy.domain'))->group(function () {
    Route::inertia('/', 'Welcome')->name('home');

    Route::get('subdomain-availability', [TenantSubdomainAvailabilityController::class, 'check'])
        ->middleware('throttle:30,1')
        ->name('subdomain.availability');

    Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle'])
        ->middleware('throttle:120,1')
        ->name('telegram.webhook');
});

require __DIR__.'/tenant.php';
```

`TenantSubdomainAvailabilityController` is created in Task 5 — leave the `use` import as-is; the route file will not be loadable until that controller exists, so do this step together with Task 5 in practice, but the routing shape belongs here. To keep this task independently testable, stub the controller now with just the `check` action (Task 5 will build it out fully):

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSubdomainAvailabilityController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        return response()->json(['available' => false]);
    }
}
```

- [ ] **Step 5: Create `routes/tenant.php`**

Move the entire body of the old `routes/web.php` auth group and the entire body of `routes/settings.php` here, unchanged except for the wrapping group and the new guard middleware:

```php
<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('tenancy.domain'))->group(function () {
    Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureStaffTenantMatchesSubdomain::class])->group(function () {
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

        Route::redirect('settings', '/settings/profile');
        Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('settings/security', [SecurityController::class, 'edit'])
            ->middleware(RequirePassword::class)
            ->name('security.edit');

        Route::put('settings/password', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');

        Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
    });
});
```

Note: `settings/profile` used `['auth']` only (not `verified`) in the old `routes/settings.php`, while `destroy`/`security`/`password`/`appearance` used `['auth', 'verified']`. Preserve that distinction exactly — split into two `Route::middleware()` blocks as the original file did, both inside the same `Route::domain()` group:

```php
Route::domain('{subdomain}.'.config('tenancy.domain'))->group(function () {
    Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureStaffTenantMatchesSubdomain::class])->group(function () {
        // dashboard, teachers, classrooms, students, guardians, attendance,
        // achievements, reports, invoices, leave-requests (as above) ...
    });

    Route::middleware(['auth', \App\Http\Middleware\EnsureStaffTenantMatchesSubdomain::class])->group(function () {
        Route::redirect('settings', '/settings/profile');
        Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureStaffTenantMatchesSubdomain::class])->group(function () {
        Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('settings/security', [SecurityController::class, 'edit'])
            ->middleware(RequirePassword::class)
            ->name('security.edit');

        Route::put('settings/password', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');

        Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
    });
});
```

Delete `routes/settings.php` and remove the `require __DIR__.'/settings.php';` line (it no longer exists in the new `routes/web.php` from Step 4).

- [ ] **Step 6: Run the test**

Run: `php artisan test --compact --filter=StaffTenantSubdomainGuardTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add routes/web.php routes/tenant.php app/Http/Controllers/TenantSubdomainAvailabilityController.php app/Http/Middleware/EnsureStaffTenantMatchesSubdomain.php tests/Feature/StaffTenantSubdomainGuardTest.php
git rm routes/settings.php
git commit -m "feat: move staff routes to per-tenant subdomain, guard cross-tenant sessions"
```

---

### Task 4: Fix existing tests broken by the domain move

**Files:**
- Modify: `tests/TestCase.php`
- Modify: `tests/Feature/MultiTenancyTest.php`
- Modify: `tests/Feature/Auth/RegistrationTest.php`
- Modify: `tests/Feature/Auth/AuthenticationTest.php:20-24` (only the one assertion)
- Modify: `tests/Feature/TelegramIntegrationTest.php:66` (only the `attendance.scan` call)
- Modify (mechanically, verified by the full suite): `tests/Feature/TeacherManagementTest.php`, `tests/Feature/MasterDataTest.php`, `tests/Feature/AttendanceTest.php`, `tests/Feature/AchievementAndReportTest.php`, `tests/Feature/SppInvoiceTest.php`, `tests/Feature/LeaveRequestTest.php`, `tests/Feature/DashboardTest.php`, `tests/Feature/Settings/ProfileUpdateTest.php`, `tests/Feature/Settings/SecurityTest.php`

**Interfaces:**
- Produces: `Tests\TestCase::actingAsStaff(User $user): static` — calls `$this->actingAs($user)` and additionally sets `URL::defaults(['subdomain' => $user->tenant->subdomain])` so every `route()` call made afterwards in the same test resolves without a missing-parameter error, exactly as it would in production once `ResolveTenantFromDomain` has run.

This task exists because every route now inside `routes/tenant.php` requires a `subdomain` route parameter. Two independent problems show up across the suite, and each has one fix:

1. **A test calls `actingAs($user)` and then `route('some.tenant.route')`.** Fix: replace `actingAs($user)` with `actingAsStaff($user)`.
2. **A test asserts a redirect using `route('dashboard', ...)` right after a raw Fortify action (login/register) that never went through `ResolveTenantFromDomain`.** Fix: assert the literal path Fortify actually redirects to (`/dashboard`) instead of computing it with `route()`, since Fortify's own `LoginResponse`/`RegisterResponse` use the raw `config('fortify.home')` string, not `route()`.

- [ ] **Step 1: Add the test helper**

```php
<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Log in as a staff user AND register their tenant's subdomain as the
     * default `route()` parameter, mirroring what ResolveTenantFromDomain
     * does for a real request to {subdomain}.{tenant_domain}.
     */
    protected function actingAsStaff(User $user): static
    {
        $this->actingAs($user);

        URL::defaults(['subdomain' => $user->tenant->subdomain]);

        return $this;
    }
}
```

- [ ] **Step 2: Fix `tests/Feature/MultiTenancyTest.php`**

Replace every `$this->actingAs($adminA)` with `$this->actingAsStaff($adminA)` (6 occurrences: the `teachers.index` test and the 5 calls inside `foreign tenant ids are rejected on every request that accepts one`). The registration test's assertion also needs updating since register now redirects to the tenant's subdomain login (Task 5), not to `dashboard`:

```php
test('new registration creates tenant and admin user', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPQ Nurul Huda',
        'subdomain' => 'tpq-nurul-huda',
        'name' => 'Ustadz Ahmad',
        'email' => 'ahmad@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $tenant = Tenant::where('name', 'TPQ Nurul Huda')->first();

    $response->assertRedirect("http://{$tenant->subdomain}.santriq.test/login?registered=1");

    $this->assertDatabaseHas('tenants', [
        'name' => 'TPQ Nurul Huda',
        'subdomain' => 'tpq-nurul-huda',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'ahmad@example.com',
        'tenant_id' => $tenant->id,
        'role' => 'admin',
    ]);
});

test('user from tenant A cannot see users from tenant B', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $adminA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    $response = $this->actingAsStaff($adminA)->get(route('teachers.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teachers/Index')
        ->has('teachers', 1)
        ->where('teachers.0.id', $adminA->id)
    );
});

test('foreign tenant ids are rejected on every request that accepts one', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $adminA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);

    $foreignClassroom = Classroom::factory()->create(['tenant_id' => $tenantB->id]);
    $foreignStudent = Student::factory()->create(['tenant_id' => $tenantB->id]);
    $foreignGuardian = Guardian::factory()->create(['tenant_id' => $tenantB->id]);

    $this->actingAsStaff($adminA)
        ->post(route('students.store'), [
            'nis' => '99999',
            'name' => 'Santri Uji',
            'gender' => 'L',
            'classroom_id' => $foreignClassroom->id,
            'guardian_ids' => [$foreignGuardian->id],
        ])
        ->assertSessionHasErrors(['classroom_id', 'guardian_ids.0']);

    $this->actingAsStaff($adminA)
        ->post(route('guardians.store'), [
            'name' => 'Wali Uji',
            'student_ids' => [$foreignStudent->id],
        ])
        ->assertSessionHasErrors('student_ids.0');

    $this->actingAsStaff($adminA)
        ->post(route('achievements.store'), [
            'student_id' => $foreignStudent->id,
            'category' => 'Hafalan',
            'title' => 'Juz 30',
            'achieved_at' => '2026-07-22',
        ])
        ->assertSessionHasErrors('student_id');

    $this->actingAsStaff($adminA)
        ->post(route('invoices.store'), [
            'student_id' => $foreignStudent->id,
            'period' => '2026-07',
            'amount' => 50000,
            'due_date' => '2026-07-31',
        ])
        ->assertSessionHasErrors('student_id');

    $this->actingAsStaff($adminA)
        ->post(route('leave-requests.store'), [
            'student_id' => $foreignStudent->id,
            'type' => 'sakit',
            'start_date' => '2026-07-22',
            'end_date' => '2026-07-23',
        ])
        ->assertSessionHasErrors('student_id');

    expect(Student::withoutGlobalScopes()->where('nis', '99999')->count())->toBe(0);
    expect(Achievement::withoutGlobalScopes()->count())->toBe(0);
    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
    expect(LeaveRequest::withoutGlobalScopes()->count())->toBe(0);
    expect($foreignGuardian->students()->count())->toBe(0);
});
```

- [ ] **Step 3: Fix `tests/Feature/Auth/RegistrationTest.php`**

```php
test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('http://tpa-nurul-huda.santriq.test/login?registered=1');
});
```

Registration no longer auto-authenticates the browser session across domains (Task 5 logs the user out again before redirecting), so drop the `$this->assertAuthenticated();` line.

- [ ] **Step 4: Fix `tests/Feature/Auth/AuthenticationTest.php`**

```php
test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
});
```

(Was `$response->assertRedirect(route('dashboard', absolute: false));` — that call now throws because `dashboard` needs a `subdomain` parameter that was never bound in this test, since login here happens on the default test host, not on a tenant subdomain.)

- [ ] **Step 5: Fix `tests/Feature/TelegramIntegrationTest.php:66`**

```php
test('attendance scan dispatches SendTelegramMessage job when guardian is linked', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '12345678',
    ]);

    $student->guardians()->attach($guardian->id, ['relation' => 'Ayah']);

    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ])->assertOk();

    Queue::assertPushed(SendTelegramMessage::class, function ($job) use ($guardian) {
        return $job->guardian->id === $guardian->id;
    });
});
```

- [ ] **Step 6: Run the suite and converge on the remaining files**

Run: `php artisan test --compact`

For every remaining failure, it will be one of exactly the two problems described above. Apply the matching fix:

- `Illuminate\Routing\Exceptions\UrlGenerationException` (missing `subdomain` parameter) right after an `actingAs($user)` call in a Feature test → change that call to `actingAsStaff($user)`. This is expected in `TeacherManagementTest.php`, `MasterDataTest.php`, `AttendanceTest.php`, `AchievementAndReportTest.php`, `SppInvoiceTest.php`, `LeaveRequestTest.php`, `DashboardTest.php`, `Settings/ProfileUpdateTest.php`, `Settings/SecurityTest.php`.
- An assertion computing `route('dashboard', ...)` (or any other `routes/tenant.php` name) right after a raw Fortify action with no preceding `actingAsStaff`/tenant-subdomain request → replace with the literal path Fortify actually redirects to.

Re-run `php artisan test --compact` after each fix until the whole suite is green.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/
git commit -m "test: adapt existing feature tests to per-tenant subdomain routing"
```

---

### Task 5: Registration collects a subdomain; register redirects to it

**Files:**
- Create: `app/Rules/NotReservedSubdomain.php`
- Modify: `app/Actions/Fortify/CreateNewUser.php`
- Create: `app/Http/Responses/RegisterResponse.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Modify: `app/Http/Controllers/TenantSubdomainAvailabilityController.php` (stubbed in Task 3)
- Modify: `resources/js/pages/auth/Register.vue`
- Test: `tests/Feature/TenantSubdomainRegistrationTest.php`

**Interfaces:**
- Consumes: `App\Rules\NotReservedSubdomain` used both by `CreateNewUser` and `TenantSubdomainAvailabilityController`.
- Produces: `POST /register` now requires a `subdomain` field; on success the browser is redirected to `https://{subdomain}.{tenant_domain}/login?registered=1` instead of the dashboard.
- Produces: `GET /subdomain-availability?value=xxx` → `{"available": true|false}`.

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Feature/TenantSubdomainRegistrationTest.php

use App\Models\Tenant;

test('registration rejects a reserved subdomain', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPQ Uji',
        'subdomain' => 'www',
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('subdomain');
});

test('registration rejects an invalid subdomain format', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPQ Uji',
        'subdomain' => 'Not Valid!',
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('subdomain');
});

test('registration rejects a subdomain already taken', function () {
    Tenant::factory()->create(['subdomain' => 'sudah-ada']);

    $this->post(route('register.store'), [
        'institution_name' => 'TPQ Uji',
        'subdomain' => 'sudah-ada',
        'name' => 'Admin',
        'email' => 'admin2@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('subdomain');
});

test('registration redirects to the new subdomain login screen', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPQ Baru',
        'subdomain' => 'tpq-baru',
        'name' => 'Admin',
        'email' => 'admin3@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('http://tpq-baru.santriq.test/login?registered=1');
    $this->assertGuest();
});

test('subdomain availability check reports taken and free values', function () {
    Tenant::factory()->create(['subdomain' => 'taken-name']);

    $this->getJson(route('subdomain.availability', ['value' => 'taken-name']))
        ->assertJson(['available' => false]);

    $this->getJson(route('subdomain.availability', ['value' => 'free-name']))
        ->assertJson(['available' => true]);

    $this->getJson(route('subdomain.availability', ['value' => 'admin']))
        ->assertJson(['available' => false]);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TenantSubdomainRegistrationTest`
Expected: FAIL — no `subdomain` validation, `TenantSubdomainAvailabilityController::check` always returns `false`.

- [ ] **Step 3: Write the reserved-word rule**

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotReservedSubdomain implements ValidationRule
{
    /**
     * @var list<string>
     */
    private const RESERVED = [
        'www', 'api', 'admin', 'app', 'mail', 'webhook', 'assets', 'static',
        'cdn', 'ftp', 'localhost', 'staging', 'dashboard', 'login', 'logout',
        'register', 'support', 'help', 'docs', 'status', 'blog', 'santriq',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array(strtolower((string) $value), self::RESERVED, true)) {
            $fail('Subdomain tersebut tidak dapat digunakan.');
        }
    }
}
```

- [ ] **Step 4: Update `CreateNewUser`**

```php
<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\NotReservedSubdomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'institution_name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required', 'string', 'min:3', 'max:63',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                'unique:tenants,subdomain',
                new NotReservedSubdomain,
            ],
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ], [], [
            'institution_name' => 'Nama Lembaga',
            'subdomain' => 'Subdomain',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $tenant = Tenant::create([
                'name' => $input['institution_name'],
                'subdomain' => strtolower($input['subdomain']),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => 'admin',
            ]);
        });
    }
}
```

`Str::random` and the slug de-dup loop are gone — subdomain uniqueness is now enforced by the `unique:tenants,subdomain` validation rule, and it's user-chosen so there's nothing left to generate.

- [ ] **Step 5: Write the `RegisterResponse`**

```php
<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        /** @var Request $request */
        $tenant = $request->user()->tenant;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $scheme = $request->getScheme();
        $url = "{$scheme}://{$tenant->subdomain}.".config('tenancy.domain').'/login?registered=1';

        return redirect()->away($url);
    }
}
```

Registration happens on the apex domain; the session Fortify just created there is useless on the tenant's subdomain (different host, no shared cookie per `docs/2026-07-23-landing-wali-login-design.md` § 4), so it's torn down immediately rather than left dangling.

- [ ] **Step 6: Bind the response and register the auth callback**

In `app/Providers/FortifyServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\RegisterResponse;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check((string) $request->password, $user->password)) {
                return null;
            }

            if (app()->bound(Tenant::class) && $user->tenant_id !== app(Tenant::class)->id) {
                return null;
            }

            return $user;
        });
    }

    // ...configureViews() and configureRateLimiting() unchanged...
}
```

Keep the rest of `configureViews()` and `configureRateLimiting()` exactly as they are today. The `authenticateUsing` callback rejects a login attempt where the credentials are correct but belong to a *different* tenant than the subdomain being visited — this is checked here (before a session is ever established) rather than only in `EnsureStaffTenantMatchesSubdomain` (Task 3), which is the defense-in-depth backstop for sessions that predate a subdomain change. When no tenant is bound (a login attempt on the bare apex host, which no in-app link ever points to), the check is skipped and Fortify's normal behavior applies.

- [ ] **Step 7: Build out `TenantSubdomainAvailabilityController`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Rules\NotReservedSubdomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantSubdomainAvailabilityController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $value = strtolower((string) $request->query('value', ''));

        $validator = Validator::make(['subdomain' => $value], [
            'subdomain' => [
                'required', 'string', 'min:3', 'max:63',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                new NotReservedSubdomain,
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['available' => false]);
        }

        return response()->json([
            'available' => ! Tenant::where('subdomain', $value)->exists(),
        ]);
    }
}
```

- [ ] **Step 8: Run the test**

Run: `php artisan test --compact --filter=TenantSubdomainRegistrationTest`
Expected: PASS.

- [ ] **Step 9: Update `Register.vue`**

Activate the `inertia-vue-development` and `wayfinder-development` skills before this step. Add the subdomain field between "Nama Lembaga" and "Nama Penanggung Jawab", with a debounced availability check against the Wayfinder-generated `subdomain.availability` route:

```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { availability } from '@/routes/subdomain';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Mulai bersama SantriQ',
        description:
            'Daftarkan lembaga Anda dan kelola santri dengan lebih mudah.',
    },
});

const subdomain = ref('');
const subdomainStatus = ref<'idle' | 'checking' | 'available' | 'taken'>('idle');
let debounceHandle: ReturnType<typeof setTimeout> | undefined;

watch(subdomain, (value) => {
    clearTimeout(debounceHandle);

    const cleaned = value.trim().toLowerCase();
    subdomain.value = cleaned;

    if (cleaned.length < 3) {
        subdomainStatus.value = 'idle';
        return;
    }

    subdomainStatus.value = 'checking';
    debounceHandle = setTimeout(async () => {
        const response = await fetch(availability.url({ query: { value: cleaned } }));
        const data = (await response.json()) as { available: boolean };
        subdomainStatus.value = data.available ? 'available' : 'taken';
    }, 400);
});
</script>

<template>
    <Head title="Daftar" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="institution_name">Nama Lembaga (TPA/TPQ)</Label>
                <Input
                    id="institution_name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    name="institution_name"
                    placeholder="Contoh: TPQ Al-Hidayah"
                />
                <InputError :message="errors.institution_name" />
            </div>

            <div class="grid gap-2">
                <Label for="subdomain">Alamat SantriQ Lembaga</Label>
                <div class="flex items-center gap-2">
                    <Input
                        id="subdomain"
                        v-model="subdomain"
                        type="text"
                        required
                        :tabindex="2"
                        name="subdomain"
                        placeholder="al-hidayah"
                    />
                    <span class="text-sm whitespace-nowrap text-muted-foreground">.santriq.web.id</span>
                </div>
                <p v-if="subdomainStatus === 'checking'" class="text-sm text-muted-foreground">Mengecek ketersediaan…</p>
                <p v-else-if="subdomainStatus === 'available'" class="text-sm text-emerald-600">Tersedia.</p>
                <p v-else-if="subdomainStatus === 'taken'" class="text-sm text-destructive">Sudah dipakai atau tidak valid.</p>
                <InputError :message="errors.subdomain" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nama Penanggung Jawab</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    :tabindex="3"
                    autocomplete="name"
                    name="name"
                    placeholder="Nama Lengkap"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="4"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Kata sandi</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Kata sandi"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi kata sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="6"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Ulangi kata sandi"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 h-11 w-full rounded-xl bg-emerald-600 font-semibold text-white hover:bg-emerald-700"
                tabindex="7"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Buat akun
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Sudah punya akun?
            <TextLink
                :href="login()"
                class="font-semibold text-emerald-700 underline underline-offset-4 dark:text-emerald-400"
                :tabindex="8"
                >Masuk</TextLink
            >
        </div>
    </Form>
</template>
```

Run `npm run build` (or restart `composer dev`) so Wayfinder regenerates `@/routes/subdomain` for the new `subdomain.availability` route.

- [ ] **Step 10: Commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add app/Rules/NotReservedSubdomain.php app/Actions/Fortify/CreateNewUser.php app/Http/Responses/RegisterResponse.php app/Providers/FortifyServiceProvider.php app/Http/Controllers/TenantSubdomainAvailabilityController.php resources/js/pages/auth/Register.vue resources/js/routes tests/Feature/TenantSubdomainRegistrationTest.php
git commit -m "feat: collect subdomain at registration, redirect to it after signup"
```

---

### Task 6: Landing content storage and the admin settings page

**Files:**
- Create: `app/Http/Requests/Settings/LembagaUpdateRequest.php`
- Create: `app/Http/Controllers/Settings/LembagaController.php`
- Modify: `routes/tenant.php`
- Create: `resources/js/pages/settings/Lembaga.vue`
- Test: `tests/Feature/Settings/LembagaSettingsTest.php`

**Interfaces:**
- Produces: `tenants.settings->landing` shape (documented in `docs/2026-07-23-landing-wali-login-design.md` § 3): `{tagline, description, logo_path, accent_color, operating_hours, gallery: string[]}`.
- Consumes: `Storage::disk('public')` for `logo` (max 1) and `gallery` (max 6) uploads.

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Feature/Settings/LembagaSettingsTest.php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can update landing content', function () {
    Storage::fake('public');

    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->put(route('lembaga.update'), [
        'tagline' => 'Belajar Al-Quran sejak dini',
        'description' => 'Deskripsi lembaga.',
        'operating_hours' => 'Senin-Jumat 15.00-17.00',
        'accent_color' => '#059669',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ]);

    $response->assertRedirect(route('lembaga.edit'));

    $tenant->refresh();
    expect($tenant->settings['landing']['tagline'])->toBe('Belajar Al-Quran sejak dini');
    expect($tenant->settings['landing']['gallery'])->toHaveCount(2);
    Storage::disk('public')->assertExists($tenant->settings['landing']['logo_path']);
});

test('pengajar cannot update landing content', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']));

    $pengajar->put(route('lembaga.update'), ['tagline' => 'Nope'])
        ->assertForbidden();
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=LembagaSettingsTest`
Expected: FAIL — route doesn't exist.

- [ ] **Step 3: Write the form request**

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class LembagaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tagline' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'max:1024'],
            'gallery' => ['nullable', 'array', 'max:6'],
            'gallery.*' => ['image', 'max:2048'],
        ];
    }
}
```

- [ ] **Step 4: Write the controller**

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LembagaController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tenant = CurrentTenant::get();

        return Inertia::render('settings/Lembaga', [
            'landing' => $tenant->settings['landing'] ?? [],
        ]);
    }

    public function update(LembagaUpdateRequest $request): RedirectResponse
    {
        $tenant = CurrentTenant::get();
        $landing = $tenant->settings['landing'] ?? [];

        foreach (['tagline', 'description', 'operating_hours', 'accent_color'] as $field) {
            if ($request->filled($field)) {
                $landing[$field] = $request->string($field)->toString();
            }
        }

        if ($request->hasFile('logo')) {
            $landing['logo_path'] = $request->file('logo')->store('tenants/'.$tenant->id.'/logo', 'public');
        }

        if ($request->hasFile('gallery')) {
            $landing['gallery'] = collect($request->file('gallery'))
                ->map(fn ($file) => $file->store('tenants/'.$tenant->id.'/gallery', 'public'))
                ->all();
        }

        $tenant->update(['settings' => [...$tenant->settings ?? [], 'landing' => $landing]]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Konten landing page diperbarui.']);

        return to_route('lembaga.edit');
    }
}
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, inside the `['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class]` group, next to the other settings routes:

```php
        Route::get('settings/lembaga', [LembagaController::class, 'edit'])->name('lembaga.edit');
        Route::put('settings/lembaga', [LembagaController::class, 'update'])->name('lembaga.update');
```

Add `use App\Http\Controllers\Settings\LembagaController;` to the top of the file.

- [ ] **Step 6: Run the test**

Run: `php artisan test --compact --filter=LembagaSettingsTest`
Expected: PASS.

- [ ] **Step 7: Write `settings/Lembaga.vue`**

Activate `inertia-vue-development` and `tailwindcss-development` first.

```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { update } from '@/routes/lembaga';

defineProps<{
    landing: {
        tagline?: string;
        description?: string;
        operating_hours?: string;
        accent_color?: string;
        logo_path?: string;
        gallery?: string[];
    };
}>();
</script>

<template>
    <Head title="Profil Landing Page" />

    <AppLayout>
        <div class="max-w-2xl space-y-6 p-4">
            <HeadingSmall
                title="Profil Landing Page"
                description="Konten ini tampil di halaman publik lembaga Anda."
            />

            <Form v-bind="update.form()" v-slot="{ errors, processing }" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="tagline">Tagline</Label>
                    <Input id="tagline" name="tagline" :default-value="landing.tagline" maxlength="150" />
                    <InputError :message="errors.tagline" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Deskripsi</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                        maxlength="2000"
                    >{{ landing.description }}</textarea>
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="operating_hours">Jam Operasional</Label>
                    <Input id="operating_hours" name="operating_hours" :default-value="landing.operating_hours" />
                    <InputError :message="errors.operating_hours" />
                </div>

                <div class="grid gap-2">
                    <Label for="accent_color">Warna Aksen</Label>
                    <Input id="accent_color" name="accent_color" type="color" :default-value="landing.accent_color ?? '#059669'" class="h-10 w-20 p-1" />
                    <InputError :message="errors.accent_color" />
                </div>

                <div class="grid gap-2">
                    <Label for="logo">Logo</Label>
                    <Input id="logo" name="logo" type="file" accept="image/*" />
                    <InputError :message="errors.logo" />
                </div>

                <div class="grid gap-2">
                    <Label for="gallery">Galeri Foto (maks. 6)</Label>
                    <Input id="gallery" name="gallery" type="file" accept="image/*" multiple />
                    <InputError :message="errors.gallery" />
                </div>

                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Simpan
                </Button>
            </Form>
        </div>
    </AppLayout>
</template>
```

If `HeadingSmall.vue` does not exist under `resources/js/components/`, check `resources/js/pages/settings/Profile.vue` for whatever heading component it already uses and match that instead — don't introduce a new one.

Run `npm run build` so Wayfinder generates `@/routes/lembaga`.

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add app/Http/Requests/Settings/LembagaUpdateRequest.php app/Http/Controllers/Settings/LembagaController.php routes/tenant.php resources/js/pages/settings/Lembaga.vue resources/js/routes tests/Feature/Settings/LembagaSettingsTest.php
git commit -m "feat: let admins edit their tenant's landing page content"
```

---

### Task 7: Public tenant landing page

**Files:**
- Create: `app/Http/Controllers/TenantLandingController.php`
- Modify: `routes/tenant.php`
- Create: `resources/js/pages/Tenant/Landing.vue`
- Test: covered by `tests/Feature/ResolveTenantFromDomainTest.php` (Task 1) plus a new stats-specific test below

**Interfaces:**
- Consumes: `App\Support\CurrentTenant::get()`.
- Produces: `GET /` on a tenant subdomain renders `Tenant/Landing` with `tenant`, `stats`, and `landing` props.

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Feature/TenantLandingTest.php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('landing page only counts this tenant students, teachers, and classrooms', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-stat']);
    $other = Tenant::factory()->create();

    Student::factory()->count(3)->create(['tenant_id' => $tenant->id, 'status' => 'active']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'status' => 'inactive']);
    Student::factory()->count(5)->create(['tenant_id' => $other->id, 'status' => 'active']);

    User::factory()->count(2)->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    $this->get("http://{$tenant->subdomain}.santriq.test/")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tenant/Landing')
            ->where('stats.students', 3)
            ->where('stats.teachers', 2)
            ->where('stats.classrooms', 2)
        );
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TenantLandingTest`
Expected: FAIL — controller doesn't exist.

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Support\CurrentTenant;
use Inertia\Inertia;
use Inertia\Response;

class TenantLandingController extends Controller
{
    public function show(): Response
    {
        $tenant = CurrentTenant::get();

        return Inertia::render('Tenant/Landing', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'address' => $tenant->address,
                'phone' => $tenant->phone,
            ],
            'landing' => $tenant->settings['landing'] ?? [],
            'stats' => [
                'students' => Student::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
                'teachers' => User::where('tenant_id', $tenant->id)->count(),
                'classrooms' => Classroom::where('tenant_id', $tenant->id)->count(),
            ],
        ]);
    }
}
```

- [ ] **Step 4: Add the route**

In `routes/tenant.php`, at the top of the `Route::domain('{subdomain}.'.config('tenancy.domain'))` group, before the `auth` sub-group:

```php
    Route::get('/', [TenantLandingController::class, 'show'])->name('tenant.landing');
```

Add `use App\Http\Controllers\TenantLandingController;`.

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact --filter=TenantLandingTest`
Expected: PASS.

Run: `php artisan test --compact --filter=ResolveTenantFromDomainTest`
Expected: still PASS (the `known subdomain resolves the matching tenant` test from Task 1 relies on this route).

- [ ] **Step 6: Write `Tenant/Landing.vue`**

Activate `inertia-vue-development` and `tailwindcss-development` first.

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { login } from '@/routes';
import { create as guardianLoginCreate } from '@/routes/guardian/login';

defineProps<{
    tenant: { id: number; name: string; address: string | null; phone: string | null };
    landing: {
        tagline?: string;
        description?: string;
        operating_hours?: string;
        accent_color?: string;
        logo_path?: string;
        gallery?: string[];
    };
    stats: { students: number; teachers: number; classrooms: number };
}>();
</script>

<template>
    <Head :title="tenant.name" />

    <div class="min-h-screen bg-background text-foreground">
        <header class="flex items-center justify-between border-b p-4">
            <div class="flex items-center gap-3">
                <img v-if="landing.logo_path" :src="`/storage/${landing.logo_path}`" :alt="tenant.name" class="h-10 w-10 rounded-full object-cover" />
                <span class="font-semibold">{{ tenant.name }}</span>
            </div>
            <div class="flex items-center gap-3">
                <ThemeToggle />
                <Link :href="guardianLoginCreate()" class="text-sm font-medium">Portal Wali</Link>
                <Link :href="login()" class="text-sm font-medium">Masuk Staf</Link>
            </div>
        </header>

        <main class="mx-auto max-w-4xl space-y-10 p-6">
            <section class="space-y-3 text-center">
                <p v-if="landing.tagline" class="text-lg text-muted-foreground">{{ landing.tagline }}</p>
                <p v-if="landing.description" class="whitespace-pre-line">{{ landing.description }}</p>
            </section>

            <section class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-3xl font-bold">{{ stats.students }}</p>
                    <p class="text-sm text-muted-foreground">Santri Aktif</p>
                </div>
                <div>
                    <p class="text-3xl font-bold">{{ stats.teachers }}</p>
                    <p class="text-sm text-muted-foreground">Pengajar & Admin</p>
                </div>
                <div>
                    <p class="text-3xl font-bold">{{ stats.classrooms }}</p>
                    <p class="text-sm text-muted-foreground">Kelas</p>
                </div>
            </section>

            <section v-if="landing.gallery?.length" class="grid grid-cols-3 gap-2">
                <img v-for="path in landing.gallery" :key="path" :src="`/storage/${path}`" class="aspect-square rounded-lg object-cover" />
            </section>

            <section v-if="landing.operating_hours || tenant.address || tenant.phone" class="space-y-1 text-center text-sm text-muted-foreground">
                <p v-if="landing.operating_hours">{{ landing.operating_hours }}</p>
                <p v-if="tenant.address">{{ tenant.address }}</p>
                <p v-if="tenant.phone">{{ tenant.phone }}</p>
            </section>
        </main>
    </div>
</template>
```

`storage/` public disk assumes `php artisan storage:link` has been run — this is standard Laravel setup, not new to this task, so it's not a separate step here.

Run `npm run build`.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add app/Http/Controllers/TenantLandingController.php routes/tenant.php resources/js/pages/Tenant/Landing.vue resources/js/routes tests/Feature/TenantLandingTest.php
git commit -m "feat: public tenant landing page with live stats"
```

---

### Task 8: Guardian auth guard

**Files:**
- Modify: `app/Models/Guardian.php`
- Modify: `config/auth.php`
- Test: `tests/Unit/GuardianAuthenticatableTest.php`

**Interfaces:**
- Produces: `Guardian implements Illuminate\Contracts\Auth\Authenticatable`, usable with `Auth::guard('guardian')`.
- Produces: `config('auth.guards.guardian')` = `['driver' => 'session', 'provider' => 'guardians']`.

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Unit/GuardianAuthenticatableTest.php

use App\Models\Guardian;
use Illuminate\Support\Facades\Auth;

test('a guardian can be logged into the guardian guard', function () {
    $guardian = Guardian::factory()->create();

    Auth::guard('guardian')->login($guardian);

    expect(Auth::guard('guardian')->id())->toBe($guardian->id);
    expect(Auth::guard('guardian')->check())->toBeTrue();
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=GuardianAuthenticatableTest`
Expected: FAIL — no `guardian` guard configured, `Guardian` isn't `Authenticatable`.

- [ ] **Step 3: Update the model**

```php
<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\GuardianFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $telegram_chat_id
 * @property string|null $link_token
 * @property Carbon|null $linked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'name', 'phone', 'telegram_chat_id', 'link_token', 'linked_at'])]
class Guardian extends Model implements AuthenticatableContract
{
    /** @use HasFactory<GuardianFactory> */
    use Authenticatable, BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Guardian $guardian) {
            if (empty($guardian->link_token)) {
                $guardian->link_token = Str::random(32);
            }
        });
    }

    /**
     * Get the students for this guardian.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student')
            ->withPivot('relation')
            ->withTimestamps();
    }
}
```

- [ ] **Step 4: Update `config/auth.php`**

```php
use App\Models\Guardian;
use App\Models\User;
```

```php
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'guardian' => [
            'driver' => 'session',
            'provider' => 'guardians',
        ],
    ],
```

```php
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        'guardians' => [
            'driver' => 'eloquent',
            'model' => Guardian::class,
        ],
    ],
```

- [ ] **Step 5: Run the test**

Run: `php artisan test --compact --filter=GuardianAuthenticatableTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Guardian.php config/auth.php tests/Unit/GuardianAuthenticatableTest.php
git commit -m "feat: add a session-based guardian auth guard"
```

---

### Task 9: Guardian magic-link request

**Files:**
- Create: `app/Http/Controllers/GuardianAuthController.php`
- Modify: `routes/tenant.php`
- Create: `resources/js/pages/guardian/Login.vue`
- Test: `tests/Feature/GuardianMagicLinkTest.php`

**Interfaces:**
- Consumes: `App\Support\CurrentTenant::get()`, `App\Jobs\SendTelegramMessage` (unchanged).
- Produces: `GET guardian.login`, `POST guardian.login.request` (throttled 5/min).

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Feature/GuardianMagicLinkTest.php

use App\Jobs\SendTelegramMessage;
use App\Models\Guardian;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;

test('requesting a link dispatches a telegram message when the guardian is linked', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-wali']);
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'phone' => '081234567890',
        'telegram_chat_id' => '999',
    ]);

    $this->post("http://{$tenant->subdomain}.santriq.test/wali/masuk", [
        'phone' => '081234567890',
    ])->assertRedirect();

    Queue::assertPushed(SendTelegramMessage::class, fn ($job) => $job->guardian->id === $guardian->id);
});

test('requesting a link fails silently useful when the guardian has no telegram link', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-wali2']);
    Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'phone' => '081111111111',
        'telegram_chat_id' => null,
    ]);

    $this->post("http://{$tenant->subdomain}.santriq.test/wali/masuk", [
        'phone' => '081111111111',
    ])->assertSessionHasErrors('phone');

    Queue::assertNothingPushed();
});

test('a phone number belonging to another tenant is rejected', function () {
    Queue::fake();

    $tenantA = Tenant::factory()->create(['subdomain' => 'tpq-a2']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'tpq-b2']);
    Guardian::factory()->create([
        'tenant_id' => $tenantB->id,
        'phone' => '082222222222',
        'telegram_chat_id' => '888',
    ]);

    $this->post("http://{$tenantA->subdomain}.santriq.test/wali/masuk", [
        'phone' => '082222222222',
    ])->assertSessionHasErrors('phone');

    Queue::assertNothingPushed();
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=GuardianMagicLinkTest`
Expected: FAIL — route doesn't exist.

- [ ] **Step 3: Write the controller (request half only — `verify`/`logout` land in Task 10)**

```php
<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramMessage;
use App\Models\Guardian;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class GuardianAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('guardian/Login');
    }

    public function requestLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $guardian = Guardian::where('tenant_id', CurrentTenant::get()->id)
            ->where('phone', $validated['phone'])
            ->first();

        if (! $guardian || empty($guardian->telegram_chat_id)) {
            return back()->withErrors([
                'phone' => 'Nomor tidak ditemukan atau belum tertaut Telegram. Hubungi pengurus lembaga.',
            ]);
        }

        $link = URL::temporarySignedRoute(
            'guardian.login.verify',
            now()->addMinutes(15),
            ['guardian' => $guardian->id]
        );

        SendTelegramMessage::dispatch(
            $guardian,
            "🔑 <b>Tautan masuk Portal Wali</b>\n\nKlik tautan berikut untuk masuk (berlaku 15 menit):\n{$link}"
        );

        return back()->with('success', 'Tautan masuk telah dikirim ke Telegram Anda.');
    }
}
```

- [ ] **Step 4: Add the routes**

In `routes/tenant.php`, inside the `Route::domain('{subdomain}.'.config('tenancy.domain'))` group but outside the staff `auth` sub-group (guardians are unauthenticated here):

```php
    Route::prefix('wali')->name('guardian.')->group(function () {
        Route::get('masuk', [GuardianAuthController::class, 'create'])->name('login');
        Route::post('masuk', [GuardianAuthController::class, 'requestLink'])
            ->middleware('throttle:5,1')
            ->name('login.request');
    });
```

Add `use App\Http\Controllers\GuardianAuthController;`.

- [ ] **Step 5: Run the test**

Run: `php artisan test --compact --filter=GuardianMagicLinkTest`
Expected: FAIL still on the two negative tests if `verify` route name doesn't resolve inside `temporarySignedRoute` — it will, since the route name just needs to exist; `verify` itself is built in Task 10. Confirm: PASS.

- [ ] **Step 6: Write `guardian/Login.vue`**

```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { request } from '@/routes/guardian/login';

defineOptions({
    layout: {
        title: 'Portal Wali',
        description: 'Masuk dengan nomor HP yang terdaftar untuk melihat kehadiran dan pencapaian anak Anda.',
    },
});
</script>

<template>
    <Head title="Portal Wali" />

    <Form v-bind="request.form()" v-slot="{ errors, processing, recentlySuccessful }" class="flex flex-col gap-6">
        <div class="grid gap-2">
            <Label for="phone">Nomor HP</Label>
            <Input id="phone" name="phone" type="tel" required autofocus placeholder="08xxxxxxxxxx" />
            <InputError :message="errors.phone" />
        </div>

        <p v-if="recentlySuccessful" class="text-sm text-emerald-600">
            Tautan masuk telah dikirim ke Telegram Anda. Buka chat dengan bot untuk melanjutkan.
        </p>

        <Button type="submit" :disabled="processing">
            <Spinner v-if="processing" />
            Kirim Tautan Masuk
        </Button>
    </Form>
</template>
```

Run `npm run build`.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add app/Http/Controllers/GuardianAuthController.php routes/tenant.php resources/js/pages/guardian/Login.vue resources/js/routes tests/Feature/GuardianMagicLinkTest.php
git commit -m "feat: guardian requests a Telegram magic-link to log in"
```

---

### Task 10: Guardian magic-link verification and logout

**Files:**
- Modify: `app/Http/Controllers/GuardianAuthController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/GuardianMagicLinkTest.php` (append)

**Interfaces:**
- Produces: `GET guardian.login.verify` (signed, 15 min), `POST guardian.logout`.

- [ ] **Step 1: Append the failing tests**

```php
test('a valid signed link logs the guardian in and redirects to the portal', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-verify']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $link = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'guardian.login.verify',
        now()->addMinutes(15),
        ['guardian' => $guardian->id, 'subdomain' => $tenant->subdomain]
    );

    $this->get($link)->assertRedirect("http://{$tenant->subdomain}.santriq.test/wali/portal");

    $this->assertAuthenticatedAs($guardian, 'guardian');
});

test('an expired or tampered link is rejected', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-expired']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $link = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'guardian.login.verify',
        now()->subMinute(),
        ['guardian' => $guardian->id, 'subdomain' => $tenant->subdomain]
    );

    $this->get($link)->assertForbidden();
    $this->assertGuest('guardian');
});

test('a guardian id from another tenant cannot be verified through this subdomain', function () {
    $tenantA = Tenant::factory()->create(['subdomain' => 'tpq-a3']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'tpq-b3']);
    $guardianB = Guardian::factory()->create(['tenant_id' => $tenantB->id]);

    $link = str_replace(
        "{$tenantB->subdomain}.santriq.test",
        "{$tenantA->subdomain}.santriq.test",
        \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'guardian.login.verify',
            now()->addMinutes(15),
            ['guardian' => $guardianB->id, 'subdomain' => $tenantB->subdomain]
        )
    );

    $this->get($link)->assertForbidden();
    $this->assertGuest('guardian');
});

test('a logged-in guardian can log out', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-out']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($guardian, 'guardian')
        ->post("http://{$tenant->subdomain}.santriq.test/wali/keluar")
        ->assertRedirect("http://{$tenant->subdomain}.santriq.test/wali/masuk");

    $this->assertGuest('guardian');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=GuardianMagicLinkTest`
Expected: FAIL — `verify`/`logout` don't exist.

- [ ] **Step 3: Add `verify` and `logout` to the controller**

Add `use Illuminate\Support\Facades\Auth;` to the top of `app/Http/Controllers/GuardianAuthController.php`, then add the two methods:

```php
    public function verify(Request $request, Guardian $guardian): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($guardian->tenant_id === CurrentTenant::get()->id, 403);

        Auth::guard('guardian')->login($guardian, remember: true);

        return redirect()->route('guardian.portal.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('guardian')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guardian.login');
    }
```

`Guardian $guardian` here resolves via implicit route-model binding without the tenant global scope being active (no `web`-guard user is authenticated on this request), so the explicit `$guardian->tenant_id === CurrentTenant::get()->id` check is load-bearing, not defensive decoration — without it, a signed link for a guardian in tenant B, replayed against tenant A's subdomain, would still resolve to a real `Guardian` row and only the tenant check stops it.

- [ ] **Step 4: Add the routes**

```php
    Route::prefix('wali')->name('guardian.')->group(function () {
        Route::get('masuk', [GuardianAuthController::class, 'create'])->name('login');
        Route::post('masuk', [GuardianAuthController::class, 'requestLink'])
            ->middleware('throttle:5,1')
            ->name('login.request');
        Route::get('masuk/verifikasi/{guardian}', [GuardianAuthController::class, 'verify'])
            ->middleware('signed')
            ->name('login.verify');
        Route::post('keluar', [GuardianAuthController::class, 'logout'])
            ->middleware('auth:guardian')
            ->name('logout');
    });
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact --filter=GuardianMagicLinkTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/GuardianAuthController.php routes/tenant.php tests/Feature/GuardianMagicLinkTest.php
git commit -m "feat: verify guardian magic-link and log out"
```

---

### Task 11: Guardian portal — dashboard and child detail

**Files:**
- Create: `app/Http/Controllers/GuardianPortalController.php`
- Modify: `routes/tenant.php`
- Create: `resources/js/pages/guardian/Portal.vue`
- Create: `resources/js/pages/guardian/StudentDetail.vue`
- Test: `tests/Feature/GuardianPortalTest.php`

**Interfaces:**
- Consumes: `Auth::guard('guardian')->user()`.
- Produces: `GET guardian.portal.index`, `GET guardian.portal.student`.

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Feature/GuardianPortalTest.php

use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

test('guardian sees only their own children on the portal dashboard', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-portal']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $ownChild = Student::factory()->create(['tenant_id' => $tenant->id]);
    $otherChild = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian->students()->attach($ownChild->id, ['relation' => 'Ayah']);

    $this->actingAs($guardian, 'guardian')
        ->get("http://{$tenant->subdomain}.santriq.test/wali/portal")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Portal')
            ->has('students', 1)
            ->where('students.0.id', $ownChild->id)
        );
});

test('guardian cannot open another guardians child detail page', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-portal2']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $otherGuardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $otherChild = Student::factory()->create(['tenant_id' => $tenant->id]);
    $otherGuardian->students()->attach($otherChild->id, ['relation' => 'Ibu']);

    $this->actingAs($guardian, 'guardian')
        ->get("http://{$tenant->subdomain}.santriq.test/wali/portal/anak/{$otherChild->id}")
        ->assertForbidden();
});

test('guardian sees attendance and achievements for their own child', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-portal3']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $child = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian->students()->attach($child->id, ['relation' => 'Ayah']);

    Attendance::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $child->id,
        'date' => now()->format('Y-m-d'),
        'status' => 'hadir',
    ]);

    $this->actingAs($guardian, 'guardian')
        ->get("http://{$tenant->subdomain}.santriq.test/wali/portal/anak/{$child->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/StudentDetail')
            ->has('attendances', 1)
        );
});
```

`AttendanceFactory` defaults `status` to `'hadir'`, matching the value used above.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=GuardianPortalTest`
Expected: FAIL — controller/routes don't exist.

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuardianPortalController extends Controller
{
    public function index(): Response
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();

        $students = $guardian->students()
            ->with('classroom')
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'classroom' => $student->classroom?->name,
                'today_status' => Attendance::where('student_id', $student->id)
                    ->where('date', now()->format('Y-m-d'))
                    ->value('status'),
            ]);

        return Inertia::render('guardian/Portal', [
            'guardian' => ['name' => $guardian->name],
            'students' => $students,
        ]);
    }

    public function show(Student $student): Response
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();

        abort_unless(
            $guardian->students()->where('students.id', $student->id)->exists(),
            403
        );

        return Inertia::render('guardian/StudentDetail', [
            'student' => ['id' => $student->id, 'name' => $student->name, 'nis' => $student->nis],
            'attendances' => Attendance::where('student_id', $student->id)
                ->latest('date')
                ->take(30)
                ->get(['date', 'checked_in_at', 'checked_out_at', 'status']),
            'achievements' => Achievement::where('student_id', $student->id)
                ->latest('achieved_at')
                ->take(20)
                ->get(['category', 'title', 'note', 'score', 'achieved_at']),
        ]);
    }
}
```

The `abort_unless($guardian->students()->where(...)->exists(), 403)` ownership check is the actual authorization boundary here, not `TenantExists` (Global Constraints) — `Student`'s `BelongsToTenant` scope only filters when a `web`-guard user is authenticated, which is never true under the `guardian` guard, so route-model binding on `{student}` can resolve a row from *any* tenant. The pivot-existence check is what actually stops cross-guardian and cross-tenant access, and it fully subsumes a tenant check since a guardian is never linked to a student outside their own tenant.

- [ ] **Step 4: Add the routes**

```php
        Route::middleware('auth:guardian')->prefix('portal')->name('portal.')->group(function () {
            Route::get('/', [GuardianPortalController::class, 'index'])->name('index');
            Route::get('anak/{student}', [GuardianPortalController::class, 'show'])->name('student');
        });
```

Nest this inside the existing `Route::prefix('wali')->name('guardian.')->group(...)` block from Task 9/10, after the `logout` route. Add `use App\Http\Controllers\GuardianPortalController;`.

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact --filter=GuardianPortalTest`
Expected: PASS.

- [ ] **Step 6: Write the Vue pages**

`resources/js/pages/guardian/Portal.vue`:

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { logout } from '@/routes/guardian';
import { student as studentRoute } from '@/routes/guardian/portal';

defineProps<{
    guardian: { name: string };
    students: Array<{ id: number; nis: string; name: string; classroom: string | null; today_status: string | null }>;
}>();
</script>

<template>
    <Head title="Portal Wali" />

    <div class="mx-auto max-w-2xl space-y-6 p-4">
        <header class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Halo, {{ guardian.name }}</h1>
            <Link :href="logout()" method="post" as="button" class="text-sm text-muted-foreground">Keluar</Link>
        </header>

        <ul class="space-y-2">
            <li v-for="student in students" :key="student.id" class="rounded-lg border p-4">
                <Link :href="studentRoute(student.id)" class="font-medium">{{ student.name }}</Link>
                <p class="text-sm text-muted-foreground">{{ student.nis }} — {{ student.classroom ?? 'Belum ada kelas' }}</p>
                <p class="text-sm">Hari ini: {{ student.today_status ?? 'Belum ada catatan' }}</p>
            </li>
        </ul>
    </div>
</template>
```

`resources/js/pages/guardian/StudentDetail.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    student: { id: number; name: string; nis: string };
    attendances: Array<{ date: string; checked_in_at: string | null; checked_out_at: string | null; status: string }>;
    achievements: Array<{ category: string; title: string; note: string | null; score: number | null; achieved_at: string }>;
}>();
</script>

<template>
    <Head :title="student.name" />

    <div class="mx-auto max-w-2xl space-y-6 p-4">
        <h1 class="text-xl font-semibold">{{ student.name }} ({{ student.nis }})</h1>

        <section>
            <h2 class="mb-2 font-medium">Kehadiran Terbaru</h2>
            <ul class="space-y-1 text-sm">
                <li v-for="a in attendances" :key="a.date">{{ a.date }} — {{ a.status }}</li>
            </ul>
        </section>

        <section>
            <h2 class="mb-2 font-medium">Pencapaian Terbaru</h2>
            <ul class="space-y-1 text-sm">
                <li v-for="(ach, i) in achievements" :key="i">
                    {{ ach.achieved_at }} — {{ ach.category }}: {{ ach.title }}
                    <span v-if="ach.score !== null">({{ ach.score }})</span>
                </li>
            </ul>
        </section>
    </div>
</template>
```

Run `npm run build`.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add app/Http/Controllers/GuardianPortalController.php routes/tenant.php resources/js/pages/guardian resources/js/routes tests/Feature/GuardianPortalTest.php
git commit -m "feat: guardian portal dashboard and child detail page"
```

---

### Task 12: Guardian self-service izin (leave requests)

**Files:**
- Create: `app/Http/Controllers/GuardianLeaveRequestController.php`
- Modify: `routes/tenant.php`
- Create: `resources/js/pages/guardian/LeaveRequests.vue`
- Test: `tests/Feature/GuardianLeaveRequestTest.php`

**Interfaces:**
- Consumes: `Auth::guard('guardian')->user()`, `App\Models\LeaveRequest` (unchanged schema).
- Produces: `GET guardian.portal.leave-requests.index`, `POST guardian.portal.leave-requests.store`.

- [ ] **Step 1: Write the failing test**

```php
<?php

// tests/Feature/GuardianLeaveRequestTest.php

use App\Models\Guardian;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;

test('guardian can submit a leave request for their own child', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-izin']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $child = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian->students()->attach($child->id, ['relation' => 'Ayah']);

    $this->actingAs($guardian, 'guardian')
        ->post("http://{$tenant->subdomain}.santriq.test/wali/portal/izin", [
            'student_id' => $child->id,
            'type' => 'sakit',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Demam',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('leave_requests', [
        'student_id' => $child->id,
        'type' => 'sakit',
        'status' => 'pending',
    ]);
});

test('guardian cannot submit a leave request for a child that is not theirs', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-izin2']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $notMyChild = Student::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($guardian, 'guardian')
        ->post("http://{$tenant->subdomain}.santriq.test/wali/portal/izin", [
            'student_id' => $notMyChild->id,
            'type' => 'sakit',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
        ])
        ->assertForbidden();

    expect(LeaveRequest::withoutGlobalScopes()->count())->toBe(0);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=GuardianLeaveRequestTest`
Expected: FAIL — route doesn't exist.

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuardianLeaveRequestController extends Controller
{
    public function index(): Response
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();
        $studentIds = $guardian->students()->pluck('students.id');

        return Inertia::render('guardian/LeaveRequests', [
            'students' => $guardian->students()->get(['students.id', 'students.name', 'students.nis']),
            'leaveRequests' => LeaveRequest::whereIn('student_id', $studentIds)
                ->with('student')
                ->latest()
                ->get(['id', 'student_id', 'type', 'start_date', 'end_date', 'reason', 'status', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:sakit,izin'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ]);

        $student = $guardian->students()->where('students.id', $validated['student_id'])->first();
        abort_unless($student !== null, 403);

        LeaveRequest::create([
            'tenant_id' => $student->tenant_id,
            'student_id' => $student->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Pengajuan izin berhasil dikirim.');
    }
}
```

Same reasoning as Task 11: `student_id` is validated as a plain integer, and ownership is re-checked against the `guardian_student` pivot rather than `TenantExists::in()`, because that rule reads `Auth::user()?->tenant_id` (the `web` guard), which is always `null` here.

- [ ] **Step 4: Add the routes**

Nest inside the `wali/portal` group from Task 11:

```php
        Route::middleware('auth:guardian')->prefix('portal')->name('portal.')->group(function () {
            Route::get('/', [GuardianPortalController::class, 'index'])->name('index');
            Route::get('anak/{student}', [GuardianPortalController::class, 'show'])->name('student');
            Route::get('izin', [GuardianLeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::post('izin', [GuardianLeaveRequestController::class, 'store'])->name('leave-requests.store');
        });
```

Add `use App\Http\Controllers\GuardianLeaveRequestController;`.

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact --filter=GuardianLeaveRequestTest`
Expected: PASS.

- [ ] **Step 6: Write `guardian/LeaveRequests.vue`**

```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/guardian/portal/leave-requests';

defineProps<{
    students: Array<{ id: number; name: string; nis: string }>;
    leaveRequests: Array<{ id: number; type: string; start_date: string; end_date: string; status: string; student: { name: string } }>;
}>();
</script>

<template>
    <Head title="Ajukan Izin" />

    <div class="mx-auto max-w-2xl space-y-6 p-4">
        <h1 class="text-xl font-semibold">Ajukan Izin / Sakit</h1>

        <Form v-bind="store.form()" v-slot="{ errors, processing }" class="space-y-4">
            <div class="grid gap-2">
                <Label for="student_id">Santri</Label>
                <select id="student_id" name="student_id" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                    <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.nis }})</option>
                </select>
                <InputError :message="errors.student_id" />
            </div>

            <div class="grid gap-2">
                <Label for="type">Jenis</Label>
                <select id="type" name="type" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                    <option value="sakit">Sakit</option>
                    <option value="izin">Izin</option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="start_date">Mulai</Label>
                    <Input id="start_date" name="start_date" type="date" />
                    <InputError :message="errors.start_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="end_date">Selesai</Label>
                    <Input id="end_date" name="end_date" type="date" />
                    <InputError :message="errors.end_date" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="reason">Alasan</Label>
                <Input id="reason" name="reason" />
                <InputError :message="errors.reason" />
            </div>

            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                Kirim Pengajuan
            </Button>
        </Form>

        <ul class="space-y-2">
            <li v-for="lr in leaveRequests" :key="lr.id" class="rounded-lg border p-3 text-sm">
                {{ lr.student.name }} — {{ lr.type }} ({{ lr.start_date }} s/d {{ lr.end_date }}) — <strong>{{ lr.status }}</strong>
            </li>
        </ul>
    </div>
</template>
```

Run `npm run build`.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add app/Http/Controllers/GuardianLeaveRequestController.php routes/tenant.php resources/js/pages/guardian/LeaveRequests.vue resources/js/routes tests/Feature/GuardianLeaveRequestTest.php
git commit -m "feat: guardian can submit and track izin from the portal"
```

---

### Task 13: Update `RENCANA-IMPLEMENTASI.md`

**Files:**
- Modify: `docs/RENCANA-IMPLEMENTASI.md`

**Interfaces:** none (documentation only).

- [ ] **Step 1: Update § 1 (Keputusan Arsitektur)**

Replace the "Identitas wali" row and add a new row, matching `docs/2026-07-23-landing-wali-login-design.md` § 2 exactly:

| Topik | Keputusan | Alasan |
|---|---|---|
| Identitas wali | Wali **bukan** user aplikasi dengan password, tapi punya guard `guardian` sendiri — login lewat magic-link Telegram, sesi persisten | Portal bisa dibuka berulang tanpa kembali ke bot tiap kali, tetap tanpa beban dukungan password |
| Lingkup subdomain | Setiap lembaga punya `{subdomain}.santriq.web.id`: landing publik, login staf, dashboard, dan portal wali semuanya di subdomain; domain utama murni marketing + registrasi | Branding konsisten per lembaga, dipilih eksplisit saat registrasi |

- [ ] **Step 2: Update § 2 (Model Data)**

Change `tenants` row's `slug` to `subdomain` in the schema listing, and add a note that `settings.landing` holds the landing page content shape from `docs/2026-07-23-landing-wali-login-design.md` § 3.

- [ ] **Step 3: Update § 0 (Status)**

Add a line noting subdomain-per-lembaga landing/wali-login shipped, referencing this plan and the design doc.

- [ ] **Step 4: Commit**

```bash
git add docs/RENCANA-IMPLEMENTASI.md
git commit -m "docs: record the subdomain landing page and guardian guard decisions"
```

---

### Task 14: Full verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full CI check**

Run: `composer ci:check`
Expected: ESLint, Prettier, `vue-tsc`, Pint, PHPStan level 7, and the full Pest suite all pass with zero failures (the 4 pre-existing Fortify 2FA skips are expected and unrelated to this work).

- [ ] **Step 2: Manually smoke-test the golden path in a browser**

Per this repo's CLAUDE.md, UI changes must be exercised in a real browser before calling the work done:
1. Set `APP_TENANT_DOMAIN=santriq.test` (or similar) in `.env`, and confirm `{anything}.santriq.test` resolves to `127.0.0.1` in your browser (modern browsers resolve `*.localhost` automatically; for a custom root like `santriq.test` add it to `/etc/hosts` or use `*.localhost` as the tenant domain locally instead).
2. `composer dev` to boot server + queue + Vite.
3. Visit the apex `/register`, pick a subdomain, submit — confirm redirect to `{subdomain}.../login?registered=1`.
4. Log in as the new admin, visit `settings/lembaga`, fill in tagline/description/logo, save.
5. Visit `{subdomain}.../` logged out — confirm the landing page shows the saved content and correct live stats.
6. Add a guardian with a phone number in the dashboard, link it to Telegram via `/start {link_token}` against your bot (or inspect the `telegram_messages` outbox row in `database-query` if no live bot is configured), then use `wali/masuk` to request a link and confirm the portal is reachable.

If no live Telegram bot is available for step 6, it's acceptable to verify the magic-link flow purely through the Pest tests from Task 9/10 and note in the handoff that the manual browser check for that specific step was skipped for that reason — say so explicitly, don't claim it was verified.

- [ ] **Step 3: Final commit if step 1 required any fixes**

```bash
git add -A
git commit -m "chore: fix issues found during full verification pass"
```

(Skip this step entirely if `composer ci:check` was already green after Task 13.)
