# Demo Tenant (demo.santriq.web.id) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Public demo lembaga at subdomain `demo`, reset hourly, with login hints on its tenant login pages so visitors can try admin, pengajar, and wali roles with zero setup.

**Architecture:** A single `App\Support\DemoTenant` constant/helper is the one source of truth for "is this the demo tenant" — consumed by the seeder, the reset command, the guardian login bypass, and the login-hint prop. Reset is a plain Artisan command run hourly by the Laravel scheduler (via a new `schedule:work` supervisor program), wiping only `students`/`guardians`/`classrooms` for the demo tenant and relying on existing `cascadeOnDelete` foreign keys to clean up everything downstream (attendances, achievements, invoices, payments, leave_requests, telegram_messages, the guardian_student pivot).

**Tech Stack:** Laravel 13 / Pest 4 / Inertia v3 + Vue 3 / Wayfinder.

## Global Constraints

- Demo tenant subdomain is the literal string `demo`, defined once in `App\Support\DemoTenant::SUBDOMAIN` — every other file references that constant, never a hardcoded `'demo'` string.
- Reset and the guardian bypass must be no-ops (404 or safe skip) for every tenant except the demo one — never gate on request input.
- Run `vendor/bin/pint --dirty --format agent` after every task that touches PHP files, before committing.
- Run `php artisan wayfinder:generate` after adding/changing any controller route so the generated TS in `resources/js/actions/` picks up the new method.
- Existing seeded users keep the emails `admin@santriq.test` / `pengajar@santriq.test`, password `password` (unchanged from current seeder).
- Tests: Pest, feature tests under `tests/Feature/`, requests use `http://{$tenant->subdomain}.santriq.test/...` per `tests/Feature/GuardianMagicLinkTest.php` convention (`APP_TENANT_DOMAIN=santriq.test` in `phpunit.xml`).

---

### Task 1: `DemoTenant` support class + reserve the subdomain

**Files:**
- Create: `app/Support/DemoTenant.php`
- Modify: `app/Rules/NotReservedSubdomain.php`
- Test: `tests/Feature/TenantSubdomainRegistrationTest.php`

**Interfaces:**
- Produces: `App\Support\DemoTenant::SUBDOMAIN` (string, `'demo'`) and `App\Support\DemoTenant::isActive(): bool` — used by every later task.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/TenantSubdomainRegistrationTest.php` (append at the end of the file, keeping the existing tests untouched):

```php
test('subdomain availability check reports the demo subdomain as reserved', function () {
    $this->getJson(route('subdomain.availability', ['value' => 'demo']))
        ->assertJson(['available' => false]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TenantSubdomainRegistrationTest`
Expected: FAIL — the new `demo` case is not in `NotReservedSubdomain::RESERVED` yet, so it reports `available: true`.

- [ ] **Step 3: Create `App\Support\DemoTenant`**

```php
<?php

namespace App\Support;

class DemoTenant
{
    public const SUBDOMAIN = 'demo';

    public static function isActive(): bool
    {
        return CurrentTenant::resolved() && CurrentTenant::get()->subdomain === self::SUBDOMAIN;
    }
}
```

- [ ] **Step 4: Add `demo` to the reserved subdomain list**

In `app/Rules/NotReservedSubdomain.php`, add the import and reference the constant instead of a bare string:

```php
use App\Support\DemoTenant;
```

Change:

```php
    private const RESERVED = [
        'www', 'api', 'admin', 'app', 'mail', 'webhook', 'assets', 'static',
        'cdn', 'ftp', 'localhost', 'staging', 'dashboard', 'login', 'logout',
        'register', 'support', 'help', 'docs', 'status', 'blog', 'santriq',
    ];
```

to:

```php
    private const RESERVED = [
        'www', 'api', 'admin', 'app', 'mail', 'webhook', 'assets', 'static',
        'cdn', 'ftp', 'localhost', 'staging', 'dashboard', 'login', 'logout',
        'register', 'support', 'help', 'docs', 'status', 'blog', 'santriq',
        DemoTenant::SUBDOMAIN,
    ];
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=TenantSubdomainRegistrationTest`
Expected: PASS (all cases, including the new one).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Support/DemoTenant.php app/Rules/NotReservedSubdomain.php tests/Feature/TenantSubdomainRegistrationTest.php
git commit -m "feat: reserve demo subdomain and add DemoTenant helper"
```

---

### Task 2: `DemoDataSeeder` with richer sample data

**Files:**
- Create: `database/seeders/DemoDataSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/DemoDataSeederTest.php`

**Interfaces:**
- Consumes: `App\Support\DemoTenant::SUBDOMAIN` (Task 1).
- Produces: `Database\Seeders\DemoDataSeeder::run(): void` — idempotent, called by both `DatabaseSeeder` and the reset command in Task 3. On each call: `Tenant::firstOrCreate` the demo tenant, `User::updateOrCreate` the two demo staff accounts (always resets their password to `password`), and — only if the tenant currently has no students — seeds 2 classrooms, 10 students, 1 guardian per student, ~2 weeks of attendance, achievements, a current-month invoice (some paid), and occasional leave requests.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DemoDataSeederTest.php`:

```php
<?php

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DemoTenant;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Hash;

test('seeding creates the demo tenant with staff, classrooms, students, guardians and history', function () {
    (new DemoDataSeeder)->run();

    $tenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->firstOrFail();

    $admin = User::where('email', 'admin@santriq.test')->firstOrFail();
    expect($admin->tenant_id)->toBe($tenant->id)
        ->and($admin->role)->toBe('admin')
        ->and(Hash::check('password', $admin->password))->toBeTrue();

    $pengajar = User::where('email', 'pengajar@santriq.test')->firstOrFail();
    expect($pengajar->tenant_id)->toBe($tenant->id)
        ->and($pengajar->role)->toBe('pengajar');

    expect(Classroom::where('tenant_id', $tenant->id)->count())->toBe(2)
        ->and(Student::where('tenant_id', $tenant->id)->count())->toBe(10)
        ->and(Guardian::where('tenant_id', $tenant->id)->count())->toBe(10);

    $student = Student::where('tenant_id', $tenant->id)->firstOrFail();
    expect($student->guardians()->count())->toBe(1)
        ->and(Attendance::where('student_id', $student->id)->count())->toBeGreaterThan(0)
        ->and(Invoice::where('student_id', $student->id)->count())->toBe(1);
});

test('seeding twice does not duplicate classrooms or students but does refresh the admin password', function () {
    (new DemoDataSeeder)->run();

    $tenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->firstOrFail();
    $admin = User::where('email', 'admin@santriq.test')->firstOrFail();
    $admin->forceFill(['password' => Hash::make('changed-by-visitor')])->save();

    (new DemoDataSeeder)->run();

    expect(Student::where('tenant_id', $tenant->id)->count())->toBe(10);

    $admin->refresh();
    expect(Hash::check('password', $admin->password))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DemoDataSeederTest`
Expected: FAIL — `Database\Seeders\DemoDataSeeder` does not exist yet.

- [ ] **Step 3: Create `DemoDataSeeder`**

```php
<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DemoTenant;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => DemoTenant::SUBDOMAIN],
            [
                'name' => 'TPQ Demo SantriQ',
                'address' => 'Jl. Contoh No. 1',
                'phone' => '08123456789',
                'timezone' => 'Asia/Jakarta',
                'settings' => ['dedup_minutes' => 5],
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@santriq.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Demo',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $pengajar = User::updateOrCreate(
            ['email' => 'pengajar@santriq.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Pengajar Demo',
                'password' => 'password',
                'role' => 'pengajar',
                'email_verified_at' => now(),
            ]
        );

        if (Student::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        Classroom::factory()
            ->count(2)
            ->sequence(['name' => 'Iqro 1'], ['name' => 'Juz Amma'])
            ->create(['tenant_id' => $tenant->id])
            ->each(function (Classroom $classroom) use ($tenant, $admin, $pengajar) {
                Student::factory()
                    ->count(5)
                    ->create([
                        'tenant_id' => $tenant->id,
                        'classroom_id' => $classroom->id,
                    ])
                    ->each(function (Student $student) use ($tenant, $admin, $pengajar) {
                        $guardian = Guardian::factory()->create([
                            'tenant_id' => $tenant->id,
                            'telegram_chat_id' => null,
                            'linked_at' => null,
                        ]);

                        $student->guardians()->attach($guardian->id, ['relation' => 'Wali']);

                        $this->seedAttendanceHistory($tenant, $student, $pengajar);
                        $this->seedAchievements($tenant, $student, $pengajar);
                        $this->seedInvoice($tenant, $student, $admin);
                        $this->seedLeaveRequest($tenant, $student);
                    });
            });
    }

    private function seedAttendanceHistory(Tenant $tenant, Student $student, User $recorder): void
    {
        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $date = now()->subDays($daysAgo);

            if ($date->isFriday()) {
                continue;
            }

            $status = fake()->randomElement(['hadir', 'hadir', 'hadir', 'hadir', 'sakit', 'izin']);

            Attendance::factory()->create([
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'date' => $date->format('Y-m-d'),
                'checked_in_at' => $status === 'hadir'
                    ? $date->clone()->setTime(15, fake()->numberBetween(30, 59))
                    : null,
                'checked_out_at' => null,
                'status' => $status,
                'recorded_by' => $recorder->id,
            ]);
        }
    }

    private function seedAchievements(Tenant $tenant, Student $student, User $recorder): void
    {
        Achievement::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'recorded_by' => $recorder->id,
        ]);
    }

    private function seedInvoice(Tenant $tenant, Student $student, User $verifier): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'period' => now()->format('Y-m'),
            'status' => fake()->boolean(60) ? 'paid' : 'unpaid',
        ]);

        if ($invoice->status === 'paid') {
            Payment::factory()->create([
                'invoice_id' => $invoice->id,
                'verified_by' => $verifier->id,
            ]);
        }
    }

    private function seedLeaveRequest(Tenant $tenant, Student $student): void
    {
        if (! fake()->boolean(30)) {
            return;
        }

        LeaveRequest::factory()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
        ]);
    }
}
```

- [ ] **Step 4: Delegate `DatabaseSeeder` to `DemoDataSeeder`**

Replace the entire body of `database/seeders/DatabaseSeeder.php` with:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed one demo lembaga so a fresh install has something to click through.
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=DemoDataSeederTest`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/seeders/DemoDataSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/DemoDataSeederTest.php
git commit -m "feat: extract DemoDataSeeder with richer sample data, subdomain demo"
```

---

### Task 3: `demo:reset` command, hourly schedule, supervisor process

**Files:**
- Create: `app/Console/Commands/ResetDemoTenant.php`
- Modify: `routes/console.php`
- Modify: `docker/supervisord.conf`
- Test: `tests/Feature/DemoResetCommandTest.php`

**Interfaces:**
- Consumes: `App\Support\DemoTenant::SUBDOMAIN` (Task 1), `Database\Seeders\DemoDataSeeder::run()` (Task 2).
- Produces: `php artisan demo:reset` — wipes and reseeds only the demo tenant; no-op (exit 0) if it doesn't exist.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DemoResetCommandTest.php`:

```php
<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DemoTenant;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Hash;

test('demo:reset is a no-op when the demo tenant does not exist', function () {
    $this->artisan('demo:reset')->assertExitCode(0);

    expect(Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->exists())->toBeFalse();
});

test('demo:reset wipes and reseeds only the demo tenant', function () {
    (new DemoDataSeeder)->run();
    $demoTenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->firstOrFail();
    $staleStudentIds = Student::where('tenant_id', $demoTenant->id)->pluck('id');

    $otherTenant = Tenant::factory()->create(['subdomain' => 'tpq-lain']);
    $otherStudent = Student::factory()->create(['tenant_id' => $otherTenant->id]);

    $admin = User::where('email', 'admin@santriq.test')->firstOrFail();
    $admin->forceFill(['password' => Hash::make('changed-by-visitor')])->save();

    $this->artisan('demo:reset')->assertExitCode(0);

    expect(Student::whereIn('id', $staleStudentIds)->exists())->toBeFalse()
        ->and(Student::where('tenant_id', $demoTenant->id)->count())->toBe(10)
        ->and(Student::where('id', $otherStudent->id)->exists())->toBeTrue();

    $admin->refresh();
    expect(Hash::check('password', $admin->password))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DemoResetCommandTest`
Expected: FAIL — `demo:reset` command does not exist ("Command \"demo:reset\" is not defined").

- [ ] **Step 3: Generate the command skeleton**

```bash
php artisan make:command ResetDemoTenant --no-interaction
```

- [ ] **Step 4: Replace the generated file content**

Replace the full content of `app/Console/Commands/ResetDemoTenant.php` with:

```php
<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Support\DemoTenant;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDemoTenant extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Wipe and reseed the public demo tenant with fresh sample data';

    public function handle(): int
    {
        $tenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->first();

        if (! $tenant) {
            $this->info('Demo tenant not found, skipping.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($tenant) {
            Guardian::where('tenant_id', $tenant->id)->delete();
            Student::where('tenant_id', $tenant->id)->delete();
            Classroom::where('tenant_id', $tenant->id)->delete();
        });

        (new DemoDataSeeder)->run();

        $this->info('Demo tenant reset.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=DemoResetCommandTest`
Expected: PASS.

- [ ] **Step 6: Schedule it hourly**

In `routes/console.php`, add the imports and schedule call:

```php
<?php

use App\Console\Commands\ResetDemoTenant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ResetDemoTenant::class)->hourly();
```

- [ ] **Step 7: Run the scheduled task locally to confirm it's registered**

Run: `php artisan schedule:list`
Expected: output includes a row for `demo:reset` with an hourly cadence (`0 * * * *`).

- [ ] **Step 8: Add the scheduler to supervisord**

In `docker/supervisord.conf`, add a new program block after `[program:queue-worker]`:

```ini
[program:scheduler]
command=php artisan schedule:work
directory=/var/www/html
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
```

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Console/Commands/ResetDemoTenant.php routes/console.php docker/supervisord.conf tests/Feature/DemoResetCommandTest.php
git commit -m "feat: add hourly demo:reset command and scheduler process"
```

Note: `docker/supervisord.conf` only takes effect after an image rebuild/redeploy — that's a separate deploy step, not part of this commit's test coverage.

---

### Task 4: Wali (guardian) demo login bypass

**Files:**
- Modify: `routes/tenant.php`
- Modify: `app/Http/Controllers/GuardianAuthController.php`
- Modify: `resources/js/pages/guardian/Login.vue`
- Test: `tests/Feature/GuardianDemoLoginTest.php`

**Interfaces:**
- Consumes: `App\Support\DemoTenant::isActive()` (Task 1).
- Produces: route `guardian.login.demo` (`POST wali/masuk-demo`); `GuardianAuthController::create()` now passes `isDemo: boolean` to `guardian/Login.vue`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/GuardianDemoLoginTest.php`:

```php
<?php

use App\Models\Guardian;
use App\Models\Tenant;
use App\Support\DemoTenant;

test('demo login bypass is unreachable on a non-demo tenant', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-bukan-demo']);
    Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $this->post("http://{$tenant->subdomain}.santriq.test/wali/masuk-demo")
        ->assertNotFound();

    $this->assertGuest('guardian');
});

test('demo login bypass 404s when the demo tenant has no guardian yet', function () {
    Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    $this->post('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/wali/masuk-demo')
        ->assertNotFound();
});

test('demo login bypass logs in as the demo tenant guardian without a signed link', function () {
    $tenant = Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $this->post('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/wali/masuk-demo')
        ->assertRedirect(route('guardian.portal.index'));

    $this->assertAuthenticatedAs($guardian, 'guardian');
});

test('the guardian login page flags isDemo only for the demo tenant', function () {
    $regular = Tenant::factory()->create(['subdomain' => 'tpq-biasa']);

    $this->get("http://{$regular->subdomain}.santriq.test/wali/masuk")
        ->assertInertia(fn ($page) => $page->where('isDemo', false));

    $demo = Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    $this->get('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/wali/masuk')
        ->assertInertia(fn ($page) => $page->where('isDemo', true));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=GuardianDemoLoginTest`
Expected: FAIL — route `wali/masuk-demo` doesn't exist (404 for the wrong reason on the first two, and a routing exception / missing `isDemo` prop on the others).

- [ ] **Step 3: Add the route**

In `routes/tenant.php`, inside the `Route::prefix('wali')->name('guardian.')->group(...)` block, add the new route right after the existing `masuk` POST route:

```php
        Route::post('masuk', [GuardianAuthController::class, 'requestLink'])
            ->middleware('throttle:5,1')
            ->name('login.request');
        Route::post('masuk-demo', [GuardianAuthController::class, 'loginDemo'])->name('login.demo');
```

- [ ] **Step 4: Add `loginDemo()` and the `isDemo` prop**

In `app/Http/Controllers/GuardianAuthController.php`, add the import:

```php
use App\Support\DemoTenant;
```

Replace the `create()` method:

```php
    public function create(): Response
    {
        return Inertia::render('guardian/Login', [
            'isDemo' => DemoTenant::isActive(),
        ]);
    }
```

Add `loginDemo()` right after `create()`:

```php
    public function loginDemo(): RedirectResponse
    {
        abort_unless(DemoTenant::isActive(), 404);

        $guardian = Guardian::where('tenant_id', CurrentTenant::get()->id)
            ->oldest('id')
            ->firstOrFail();

        Auth::guard('guardian')->login($guardian, remember: true);

        return redirect()->route('guardian.portal.index');
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=GuardianDemoLoginTest`
Expected: PASS.

- [ ] **Step 6: Regenerate Wayfinder TypeScript**

```bash
php artisan wayfinder:generate
```

Expected: `resources/js/actions/App/Http/Controllers/GuardianAuthController.ts` now exports a `loginDemo` function alongside `create`, `requestLink`, `verify`, `logout`.

- [ ] **Step 7: Update `guardian/Login.vue`**

Replace the full content of `resources/js/pages/guardian/Login.vue` with:

```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import GuardianAuthController from '@/actions/App/Http/Controllers/GuardianAuthController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Portal Wali',
        description:
            'Masuk dengan nomor HP yang terdaftar untuk melihat kehadiran dan pencapaian anak Anda.',
    },
});

defineProps<{
    isDemo?: boolean;
}>();
</script>

<template>
    <Head title="Portal Wali" />

    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">Portal Wali Santri</h1>
                <p class="text-sm text-muted-foreground">
                    Masukkan nomor HP yang terdaftar untuk menerima tautan masuk
                    via Telegram.
                </p>
            </div>

            <div
                v-if="isDemo"
                class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center text-sm dark:border-emerald-900 dark:bg-emerald-950"
            >
                <p class="mb-3 text-emerald-800 dark:text-emerald-300">
                    Ini tenant demo — coba masuk sebagai wali tanpa Telegram.
                </p>
                <Form
                    v-bind="GuardianAuthController.loginDemo.form()"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-700"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Masuk sebagai Wali Demo
                    </Button>
                </Form>
            </div>

            <Form
                action="/wali/masuk"
                method="post"
                v-slot="{ errors, processing, recentlySuccessful }"
                class="flex flex-col gap-6"
            >
                <div class="grid gap-2">
                    <Label for="phone">Nomor HP</Label>
                    <Input
                        id="phone"
                        name="phone"
                        type="tel"
                        required
                        autofocus
                        placeholder="08xxxxxxxxxx"
                    />
                    <InputError :message="errors.phone" />
                </div>

                <p v-if="recentlySuccessful" class="text-sm text-emerald-600">
                    Tautan masuk telah dikirim ke Telegram Anda. Buka chat
                    dengan bot untuk melanjutkan.
                </p>

                <Button
                    type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Kirim Tautan Masuk
                </Button>
            </Form>
        </div>
    </div>
</template>
```

- [ ] **Step 8: Format, typecheck and commit**

```bash
vendor/bin/pint --dirty --format agent
npm run types:check
git add routes/tenant.php app/Http/Controllers/GuardianAuthController.php resources/js/pages/guardian/Login.vue resources/js/actions/App/Http/Controllers/GuardianAuthController.ts tests/Feature/GuardianDemoLoginTest.php
git commit -m "feat: add wali demo login bypass for the demo tenant"
```

---

### Task 5: Login hint on the demo tenant's staff login page

**Files:**
- Modify: `app/Providers/FortifyServiceProvider.php`
- Modify: `resources/js/pages/auth/Login.vue`
- Test: `tests/Feature/DemoLoginHintTest.php`

**Interfaces:**
- Consumes: `App\Support\DemoTenant::isActive()` (Task 1), `guardian.login` route / `GuardianAuthController::create` (Task 4, for the wali link).
- Produces: `auth/Login` Inertia prop `demoHint: { admin: {email, password}, pengajar: {email, password} } | null`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DemoLoginHintTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Support\DemoTenant;

test('the login page shows demo credentials only on the demo tenant', function () {
    $regular = Tenant::factory()->create(['subdomain' => 'tpq-biasa']);

    $this->get("http://{$regular->subdomain}.santriq.test/login")
        ->assertInertia(fn ($page) => $page->where('demoHint', null));

    Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    $this->get('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/login')
        ->assertInertia(fn ($page) => $page
            ->where('demoHint.admin.email', 'admin@santriq.test')
            ->where('demoHint.admin.password', 'password')
            ->where('demoHint.pengajar.email', 'pengajar@santriq.test')
            ->where('demoHint.pengajar.password', 'password')
        );
});

test('the login page has no demo hint on the bare apex domain', function () {
    $this->get('http://santriq.test/login')
        ->assertInertia(fn ($page) => $page->where('demoHint', null));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DemoLoginHintTest`
Expected: FAIL — `demoHint` prop doesn't exist on `auth/Login` yet.

- [ ] **Step 3: Add the `demoHint` prop**

In `app/Providers/FortifyServiceProvider.php`, add the import:

```php
use App\Support\DemoTenant;
```

Replace the `loginView` closure body:

```php
        Fortify::loginView(function (Request $request) {
            $tenant = CurrentTenant::resolved() ? CurrentTenant::get() : null;
            $landing = $tenant?->settings['landing'] ?? [];

            return Inertia::render('auth/Login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'status' => $request->session()->get('status'),
                'tenantBrand' => $tenant ? [
                    'name' => $tenant->name,
                    'logo_path' => $landing['logo_path'] ?? null,
                    'tagline' => $landing['tagline'] ?? 'Tumbuh dalam ilmu, dekat dalam kebersamaan.',
                    'description' => $landing['description'] ?? "{$tenant->name} mendampingi santri belajar Al-Qur'an, bertumbuh dalam adab, dan berkembang bersama.",
                ] : null,
                'demoHint' => DemoTenant::isActive() ? [
                    'admin' => ['email' => 'admin@santriq.test', 'password' => 'password'],
                    'pengajar' => ['email' => 'pengajar@santriq.test', 'password' => 'password'],
                ] : null,
            ]);
        });
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=DemoLoginHintTest`
Expected: PASS.

- [ ] **Step 5: Update `auth/Login.vue`**

Replace the full content of `resources/js/pages/auth/Login.vue` with:

```vue
<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { redirect as googleRedirect } from '@/actions/App/Http/Controllers/GoogleAuthController';
import GuardianAuthController from '@/actions/App/Http/Controllers/GuardianAuthController';
import AlertError from '@/components/AlertError.vue';
import GoogleAuthButton from '@/components/GoogleAuthButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Selamat datang kembali',
        description: 'Masuk untuk melanjutkan pengelolaan lembaga Anda.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    tenantBrand?: Record<string, unknown> | null;
    demoHint?: {
        admin: { email: string; password: string };
        pengajar: { email: string; password: string };
    } | null;
}>();

const page = usePage<{
    subdomain?: string | null;
    errors?: Record<string, string>;
}>();
const googleLoginUrl = computed(() =>
    googleRedirect.url({
        query: { intent: 'login', subdomain: page.props.subdomain ?? '' },
    }),
);
const googleError = computed(() => page.props.errors?.google);
const guardianLoginUrl = computed(() => GuardianAuthController.create());
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div
        v-if="demoHint"
        class="mb-6 space-y-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm dark:border-emerald-900 dark:bg-emerald-950"
    >
        <p class="font-semibold text-emerald-800 dark:text-emerald-300">
            Coba SantriQ tanpa daftar
        </p>
        <p class="text-emerald-700 dark:text-emerald-400">
            Admin:
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.admin.email
            }}</code>
            /
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.admin.password
            }}</code>
        </p>
        <p class="text-emerald-700 dark:text-emerald-400">
            Pengajar:
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.pengajar.email
            }}</code>
            /
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.pengajar.password
            }}</code>
        </p>
        <TextLink :href="guardianLoginUrl" class="inline-block font-semibold">
            Coba sebagai Wali Santri &rarr;
        </TextLink>
    </div>

    <AlertError
        v-if="googleError"
        :errors="[googleError]"
        title="Masuk dengan Google gagal"
        class="mb-6"
    />

    <GoogleAuthButton
        :href="googleLoginUrl"
        label="Masuk dengan Google"
        class="mb-6"
    />

    <div class="relative mb-6 text-center text-sm text-muted-foreground">
        <span class="relative z-10 bg-background px-2">atau</span>
        <div class="absolute inset-x-0 top-1/2 -z-0 border-t"></div>
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Kata sandi</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Lupa kata sandi?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Kata sandi"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Ingat saya</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 h-11 w-full rounded-xl bg-emerald-600 font-semibold text-white hover:bg-emerald-700"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Masuk
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Belum punya akun?
            <TextLink
                :href="register()"
                class="font-semibold text-emerald-700 dark:text-emerald-400"
                :tabindex="5"
                >Daftar gratis</TextLink
            >
        </div>
    </Form>
</template>
```

- [ ] **Step 6: Format, typecheck and commit**

```bash
vendor/bin/pint --dirty --format agent
npm run types:check
git add app/Providers/FortifyServiceProvider.php resources/js/pages/auth/Login.vue tests/Feature/DemoLoginHintTest.php
git commit -m "feat: show demo login hints on the demo tenant's login page"
```

---

### Final check

- [ ] Run the full suite: `composer test`
- [ ] Run `npm run build` and open `http://demo.santriq.test/login` (or the local equivalent) to eyeball the hint card, then click through to `/wali/masuk` and confirm the demo wali button logs in.
- [ ] Confirm `php artisan schedule:list` still shows `demo:reset` hourly.
