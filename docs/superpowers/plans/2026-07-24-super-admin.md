# Panel Super Admin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a cross-tenant super admin capability: a `users.is_super_admin` flag (independent of the existing per-tenant `role`), an apex-domain `/super-admin` panel listing every lembaga with basic stats, a suspend/activate toggle, and enforcement that a suspended lembaga is unreachable on its subdomain.

**Architecture:** `is_super_admin` is a boolean on `users`, orthogonal to `role` — a super admin is still a normal tenant admin/pengajar in their own lembaga, plus this extra capability. The panel lives under `Route::domain(config('tenancy.domain'))` in `routes/web.php` (the existing apex-only group), gated by `TenantPolicy` via `Gate::authorize(...)` in the controller — the same pattern `TeacherController` already uses for `UserPolicy`, so no new middleware class is introduced. Suspension is a nullable `tenants.suspended_at` timestamp enforced once, in the global `ResolveTenantFromDomain` middleware, so every route on a suspended tenant's subdomain 403s before reaching any controller.

**Tech Stack:** Laravel 13 / Fortify / Inertia v3 / Vue 3 / Pest 4 / Wayfinder.

## Global Constraints

- Foreign-key-shaped request input must validate via `App\Rules\TenantExists::in(...)`, not `exists:` — not applicable here (no tenant-scoped request input in this feature).
- Validasi di Form Request, otorisasi di Policy — this feature has no user-submitted fields (only a route-model-bound toggle), so authorization is the only concern; it goes through `TenantPolicy` + `Gate::authorize()`, matching `TeacherController`'s existing pattern.
- Frontend calls routes via Wayfinder (`@/routes/...`), never hardcoded URLs.
- PHP: curly braces always, constructor promotion, explicit return types, PHPDoc array shapes over inline comments.
- Run `vendor/bin/pint --dirty --format agent` after any PHP change, before considering a task done.
- Run `php artisan test --compact --filter=<Test>` for the affected test after every task.

---

## File Map

- `database/migrations/2026_07_24_100000_add_is_super_admin_to_users_table.php` — new
- `database/migrations/2026_07_24_100100_add_suspended_at_to_tenants_table.php` — new
- `app/Models/User.php` — modify: `is_super_admin` property/cast/Fillable, `isSuperAdmin()`
- `app/Models/Tenant.php` — modify: `suspended_at` property/cast/Fillable, `isSuspended()`, `students()`, `guardians()`
- `database/factories/UserFactory.php` — modify: `superAdmin()` state
- `database/factories/TenantFactory.php` — modify: `suspended()` state
- `app/Policies/TenantPolicy.php` — new
- `app/Http/Controllers/SuperAdminController.php` — new
- `routes/web.php` — modify: register `/super-admin` routes
- `app/Http/Middleware/ResolveTenantFromDomain.php` — modify: 403 on suspended tenant
- `app/Http/Middleware/HandleInertiaRequests.php` — modify: share `superAdminUrl`
- `resources/js/types/auth.ts` — modify: `User.is_super_admin`, fix `Tenant.slug` → `subdomain`, add `Tenant.suspended_at`
- `resources/js/components/AppSidebar.vue` — modify: super-admin nav link
- `resources/js/layouts/SuperAdminLayout.vue` — new
- `resources/js/pages/SuperAdmin/Index.vue` — new
- `resources/js/pages/SuperAdmin/Show.vue` — new
- `tests/Feature/SuperAdminTest.php` — new
- `tests/Feature/TenantSuspensionTest.php` — new

---

### Task 1: Data model — `is_super_admin` flag and `suspended_at` timestamp

**Files:**
- Create: `database/migrations/2026_07_24_100000_add_is_super_admin_to_users_table.php`
- Create: `database/migrations/2026_07_24_100100_add_suspended_at_to_tenants_table.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Tenant.php`
- Modify: `database/factories/UserFactory.php`
- Modify: `database/factories/TenantFactory.php`
- Test: `tests/Feature/SuperAdminTest.php`

**Interfaces:**
- Produces: `User::isSuperAdmin(): bool`, `Tenant::isSuspended(): bool`, `Tenant::students(): HasMany<Student>`, `Tenant::guardians(): HasMany<Guardian>`, `UserFactory::superAdmin(): static`, `TenantFactory::suspended(): static`. Every later task relies on these exact names.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SuperAdminTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;

test('is_super_admin flag and tenant suspension helpers work', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_super_admin' => true]);

    expect($user->isSuperAdmin())->toBeTrue();
    expect($tenant->isSuspended())->toBeFalse();

    $tenant->update(['suspended_at' => now()]);

    expect($tenant->fresh()->isSuspended())->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SuperAdminTest`
Expected: FAIL — `is_super_admin` isn't a fillable/existing column yet, so `$user->isSuperAdmin()` errors or returns false; `Tenant::isSuspended()` doesn't exist yet (BadMethodCallException).

- [ ] **Step 3: Create the migrations**

`database/migrations/2026_07_24_100000_add_is_super_admin_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
```

`database/migrations/2026_07_24_100100_add_suspended_at_to_tenants_table.php`:

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
            $table->timestamp('suspended_at')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('suspended_at');
        });
    }
};
```

Run: `php artisan migrate`
Expected: both migrations run successfully.

- [ ] **Step 4: Update `User` model**

In `app/Models/User.php`:

1. Add to the `@property` PHPDoc block (after `* @property string $role`):

```php
 * @property bool $is_super_admin
```

2. Update the `#[Fillable]` attribute:

```php
#[Fillable(['name', 'email', 'password', 'google_id', 'email_verified_at', 'tenant_id', 'role', 'is_super_admin', 'onboarded_at'])]
```

3. Add `'is_super_admin' => 'boolean'` to the `casts()` array:

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'onboarded_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
    ];
}
```

4. Add the method next to `isPengajar()`:

```php
public function isSuperAdmin(): bool
{
    return $this->is_super_admin;
}
```

- [ ] **Step 5: Update `Tenant` model**

In `app/Models/Tenant.php`:

1. Add to the `@property` PHPDoc block (after `* @property array<string, mixed>|null $settings`):

```php
 * @property Carbon|null $suspended_at
```

2. Update the `#[Fillable]` attribute:

```php
#[Fillable(['name', 'subdomain', 'address', 'phone', 'timezone', 'settings', 'suspended_at'])]
```

3. Add `'suspended_at' => 'datetime'` to `casts()`:

```php
protected function casts(): array
{
    return [
        'settings' => 'array',
        'suspended_at' => 'datetime',
    ];
}
```

4. Add relations and the helper method (needs `use Illuminate\Database\Eloquent\Relations\HasMany;` already imported):

```php
/**
 * Get students belonging to this tenant.
 *
 * @return HasMany<Student, $this>
 */
public function students(): HasMany
{
    return $this->hasMany(Student::class);
}

/**
 * Get guardians belonging to this tenant.
 *
 * @return HasMany<Guardian, $this>
 */
public function guardians(): HasMany
{
    return $this->hasMany(Guardian::class);
}

public function isSuspended(): bool
{
    return $this->suspended_at !== null;
}
```

Add the two new model imports at the top of the file:

```php
use App\Models\Guardian;
use App\Models\Student;
```

- [ ] **Step 6: Add factory states**

In `database/factories/UserFactory.php`, add next to `admin()`:

```php
public function superAdmin(): static
{
    return $this->state(fn (array $attributes) => [
        'is_super_admin' => true,
    ]);
}
```

In `database/factories/TenantFactory.php`, add a new method:

```php
public function suspended(): static
{
    return $this->state(fn (array $attributes) => [
        'suspended_at' => now(),
    ]);
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --compact --filter=SuperAdminTest`
Expected: PASS

- [ ] **Step 8: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add database/migrations/2026_07_24_100000_add_is_super_admin_to_users_table.php \
        database/migrations/2026_07_24_100100_add_suspended_at_to_tenants_table.php \
        app/Models/User.php app/Models/Tenant.php \
        database/factories/UserFactory.php database/factories/TenantFactory.php \
        tests/Feature/SuperAdminTest.php
git commit -m "feat: add is_super_admin flag and tenant suspension timestamp"
```

---

### Task 2: `/super-admin` panel — policy, controller, routes

**Files:**
- Create: `app/Policies/TenantPolicy.php`
- Create: `app/Http/Controllers/SuperAdminController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/SuperAdminTest.php` (append)

**Interfaces:**
- Consumes: `User::isSuperAdmin()`, `Tenant::isSuspended()`, `Tenant::students()`, `Tenant::guardians()` (Task 1).
- Produces: routes `super-admin.index` (GET `/super-admin`), `super-admin.show` (GET `/super-admin/{tenant}`), `super-admin.toggle-status` (PATCH `/super-admin/{tenant}/toggle-status`). Inertia components `SuperAdmin/Index` (prop `tenants: array` with `students_count`/`teachers_count`/`guardians_count`) and `SuperAdmin/Show` (props `tenant`, `staff: User[]`). Task 4 relies on `route('super-admin.index')` existing; Task 5 relies on the exact prop shapes above.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/SuperAdminTest.php`:

```php
test('guest cannot access super admin panel', function () {
    $this->get(route('super-admin.index'))->assertRedirect(route('login'));
});

test('regular tenant admin cannot access super admin panel', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)->get(route('super-admin.index'))->assertForbidden();
});

test('super admin sees all tenants with stats on the index page', function () {
    $ownTenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $ownTenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $other = Tenant::factory()->create();
    User::factory()->count(2)->create(['tenant_id' => $other->id, 'role' => 'pengajar']);
    \App\Models\Student::factory()->count(3)->create(['tenant_id' => $other->id]);
    \App\Models\Guardian::factory()->count(4)->create(['tenant_id' => $other->id]);

    $response = $this->actingAsStaff($superAdmin)
        ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1'])
        ->get(route('super-admin.index'));

    $response->assertOk();
    $row = collect($response->json('props.tenants'))->firstWhere('id', $other->id);

    expect($row['students_count'])->toBe(3);
    expect($row['teachers_count'])->toBe(2);
    expect($row['guardians_count'])->toBe(4);
});

test('super admin sees tenant detail with staff list', function () {
    $ownTenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $ownTenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $other = Tenant::factory()->create();
    $teacher = User::factory()->create(['tenant_id' => $other->id, 'role' => 'pengajar']);

    $response = $this->actingAsStaff($superAdmin)->get(route('super-admin.show', $other));

    $response->assertInertia(fn ($page) => $page
        ->component('SuperAdmin/Show')
        ->where('tenant.id', $other->id)
        ->where('staff.0.id', $teacher->id)
    );
});

test('super admin can suspend and reactivate a tenant', function () {
    $ownTenant = Tenant::factory()->create();
    $superAdmin = User::factory()->create(['tenant_id' => $ownTenant->id, 'role' => 'admin', 'is_super_admin' => true]);
    $target = Tenant::factory()->create();

    $this->actingAsStaff($superAdmin)
        ->patch(route('super-admin.toggle-status', $target))
        ->assertRedirect();

    expect($target->fresh()->isSuspended())->toBeTrue();

    $this->actingAsStaff($superAdmin)
        ->patch(route('super-admin.toggle-status', $target))
        ->assertRedirect();

    expect($target->fresh()->isSuspended())->toBeFalse();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=SuperAdminTest`
Expected: FAIL — `route('super-admin.index')` etc. don't exist yet (`RouteNotFoundException`).

- [ ] **Step 3: Create `TenantPolicy`**

`app/Policies/TenantPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin();
    }
}
```

- [ ] **Step 4: Create `SuperAdminController`**

`app/Http/Controllers/SuperAdminController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Tenant::class);

        $tenants = Tenant::withCount([
            'students',
            'guardians',
            'users as teachers_count' => fn ($query) => $query->where('role', 'pengajar'),
        ])->latest()->get();

        return Inertia::render('SuperAdmin/Index', [
            'tenants' => $tenants,
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $tenant->loadCount([
            'students',
            'guardians',
            'users as teachers_count' => fn ($query) => $query->where('role', 'pengajar'),
        ]);

        return Inertia::render('SuperAdmin/Show', [
            'tenant' => $tenant,
            'staff' => $tenant->users()->latest()->get(),
        ]);
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        Gate::authorize('update', $tenant);

        $tenant->update(['suspended_at' => $tenant->isSuspended() ? null : now()]);

        return redirect()->back()->with('success', $tenant->isSuspended()
            ? 'Lembaga berhasil disuspend.'
            : 'Lembaga berhasil diaktifkan kembali.');
    }
}
```

- [ ] **Step 5: Register routes**

In `routes/web.php`, add the import:

```php
use App\Http\Controllers\SuperAdminController;
```

Inside the existing `Route::domain(config('tenancy.domain'))->group(function () { ... })` block, after the `telegram/webhook` route registration and before the closing `});`, add:

```php
Route::middleware(['auth', 'verified'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('index');
    Route::get('{tenant}', [SuperAdminController::class, 'show'])->name('show');
    Route::patch('{tenant}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('toggle-status');
});
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=SuperAdminTest`
Expected: PASS

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Policies/TenantPolicy.php app/Http/Controllers/SuperAdminController.php routes/web.php tests/Feature/SuperAdminTest.php
git commit -m "feat: add super admin panel controller and routes"
```

---

### Task 3: Enforce tenant suspension

**Files:**
- Modify: `app/Http/Middleware/ResolveTenantFromDomain.php`
- Test: `tests/Feature/TenantSuspensionTest.php`

**Interfaces:**
- Consumes: `Tenant::isSuspended()` (Task 1).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TenantSuspensionTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;

test('suspended tenant landing page is inaccessible', function () {
    $tenant = Tenant::factory()->create(['suspended_at' => now()]);

    $this->get("http://{$tenant->subdomain}.santriq.test/")->assertForbidden();
});

test('suspended tenant blocks staff dashboard access', function () {
    $tenant = Tenant::factory()->create(['suspended_at' => now()]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)
        ->get("http://{$tenant->subdomain}.santriq.test/dashboard")
        ->assertForbidden();
});

test('active tenant landing page is still reachable', function () {
    $tenant = Tenant::factory()->create();

    $this->get("http://{$tenant->subdomain}.santriq.test/")->assertOk();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TenantSuspensionTest`
Expected: FAIL — the first two tests get 200/302 instead of 403 (suspension isn't enforced yet).

- [ ] **Step 3: Enforce suspension in the middleware**

In `app/Http/Middleware/ResolveTenantFromDomain.php`, change:

```php
        if ($subdomain !== null) {
            $tenant = Tenant::where('subdomain', $subdomain)->first();

            abort_unless($tenant !== null, 404);

            app()->instance(Tenant::class, $tenant);
            URL::defaults(['subdomain' => $subdomain]);
        } elseif ($request->user('web') && $request->is('login', 'register')) {
```

to:

```php
        if ($subdomain !== null) {
            $tenant = Tenant::where('subdomain', $subdomain)->first();

            abort_unless($tenant !== null, 404);
            abort_if($tenant->isSuspended(), 403);

            app()->instance(Tenant::class, $tenant);
            URL::defaults(['subdomain' => $subdomain]);
        } elseif ($request->user('web') && $request->is('login', 'register')) {
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=TenantSuspensionTest`
Expected: PASS

- [ ] **Step 5: Run the full suite to check for regressions**

Run: `php artisan test --compact`
Expected: PASS (no existing test relies on a non-suspended tenant behaving differently, but this confirms it).

- [ ] **Step 6: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Http/Middleware/ResolveTenantFromDomain.php tests/Feature/TenantSuspensionTest.php
git commit -m "feat: block access to suspended tenants"
```

---

### Task 4: Sidebar link to the super admin panel

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `resources/js/types/auth.ts`
- Modify: `resources/js/components/AppSidebar.vue`
- Modify: `tests/Feature/SuperAdminTest.php` (append)

**Interfaces:**
- Consumes: `User::isSuperAdmin()` (Task 1), route `super-admin.index` (Task 2).
- Produces: shared Inertia prop `superAdminUrl: string | null`.

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/SuperAdminTest.php`:

```php
test('superAdminUrl prop is shared only with super admins', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    $superAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => true]);

    $this->actingAsStaff($admin)
        ->get(route('dashboard', ['subdomain' => $tenant->subdomain]))
        ->assertInertia(fn ($page) => $page->where('superAdminUrl', null));

    $this->actingAsStaff($superAdmin)
        ->get(route('dashboard', ['subdomain' => $tenant->subdomain]))
        ->assertInertia(fn ($page) => $page->where('superAdminUrl', route('super-admin.index')));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SuperAdminTest`
Expected: FAIL — `superAdminUrl` prop is missing entirely (Inertia assertion fails to find the key).

- [ ] **Step 3: Share the prop**

In `app/Http/Middleware/HandleInertiaRequests.php`, add a line to the returned array (after `'subdomain' => ...`):

```php
            'subdomain' => CurrentTenant::resolved() ? CurrentTenant::get()->subdomain : $user?->tenant?->subdomain,
            'superAdminUrl' => $user?->isSuperAdmin() ? route('super-admin.index') : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=SuperAdminTest`
Expected: PASS

- [ ] **Step 5: Update the frontend `User`/`Tenant` types**

In `resources/js/types/auth.ts`, fix the stale `slug` field (the underlying column was renamed to `subdomain` — see `2026_07_23_100000_rename_slug_to_subdomain_on_tenants_table.php` — and nothing in the frontend currently reads `slug`) and add `suspended_at`, then add `is_super_admin` to `User`:

```ts
export type Tenant = {
    id: number;
    name: string;
    subdomain: string;
    address: string | null;
    phone: string | null;
    timezone: string;
    settings: Record<string, unknown> | null;
    suspended_at: string | null;
};

export type User = {
    id: number;
    tenant_id: number | null;
    name: string;
    email: string;
    role: 'admin' | 'pengajar';
    is_super_admin: boolean;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    tenant?: Tenant | null;
    [key: string]: unknown;
};
```

- [ ] **Step 6: Add the sidebar link**

In `resources/js/components/AppSidebar.vue`, add the `ShieldCheck` icon to the existing `@lucide/vue` import:

```ts
import {
    Award,
    BarChart3,
    CalendarCheck,
    CreditCard,
    FileText,
    GraduationCap,
    LayoutGrid,
    QrCode,
    ShieldCheck,
    UserCheck,
    UserCog,
    Users,
} from '@lucide/vue';
```

Add a computed property next to `currentUser`:

```ts
const superAdminUrl = computed(() => page.props.superAdminUrl as string | null);
```

In the template, add a menu item in `SidebarFooter`, above `<NavUser />` (native `<a>`, not `<Link>`, because this crosses from the tenant subdomain to the apex domain — an Inertia `<Link>` can't navigate across hosts):

```vue
        <SidebarFooter>
            <SidebarMenu v-if="superAdminUrl">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <a :href="superAdminUrl">
                            <ShieldCheck />
                            <span>Panel Super Admin</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
```

- [ ] **Step 7: Type-check and lint the frontend**

Run: `npm run types:check`
Expected: no errors.

Run: `npm run lint`
Expected: no errors (auto-fixes are fine to accept).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php resources/js/types/auth.ts \
        resources/js/components/AppSidebar.vue tests/Feature/SuperAdminTest.php
git commit -m "feat: link to the super admin panel from the tenant sidebar"
```

---

### Task 5: Super admin pages

**Files:**
- Create: `resources/js/layouts/SuperAdminLayout.vue`
- Create: `resources/js/pages/SuperAdmin/Index.vue`
- Create: `resources/js/pages/SuperAdmin/Show.vue`

**Interfaces:**
- Consumes: Inertia components `SuperAdmin/Index` (prop `tenants: (Tenant & { students_count, teachers_count, guardians_count, created_at })[]`) and `SuperAdmin/Show` (props `tenant`, `staff: User[]`) from Task 2; routes `index`/`show`/`toggleStatus` from `@/routes/super-admin` (generated by Wayfinder from Task 2's route names); `logout` from `@/routes` (existing).

- [ ] **Step 1: Regenerate Wayfinder route helpers**

Run: `npm run build`
Expected: build succeeds; `resources/js/routes/super-admin/index.ts` is generated, exporting `index`, `show`, `toggleStatus`.

- [ ] **Step 2: Create `SuperAdminLayout.vue`**

`resources/js/layouts/SuperAdminLayout.vue`:

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { logout } from '@/routes';
import { index } from '@/routes/super-admin';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
            >
                <Link :href="index()" class="flex items-center gap-2">
                    <AppLogo />
                    <span class="text-sm font-medium text-muted-foreground">
                        Super Admin
                    </span>
                </Link>
                <Button as-child variant="outline" size="sm">
                    <Link :href="logout()" method="post">Keluar</Link>
                </Button>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-6 py-8">
            <slot />
        </main>

        <Toaster />
    </div>
</template>
```

- [ ] **Step 3: Create `SuperAdmin/Index.vue`**

`resources/js/pages/SuperAdmin/Index.vue`:

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import { show, toggleStatus } from '@/routes/super-admin';
import type { Tenant } from '@/types/auth';

type TenantRow = Tenant & {
    students_count: number;
    teachers_count: number;
    guardians_count: number;
    created_at: string;
};

defineProps<{
    tenants: TenantRow[];
}>();

defineOptions({
    layout: SuperAdminLayout,
});
</script>

<template>
    <Head title="Panel Super Admin" />

    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Daftar Lembaga</h1>
            <p class="text-sm text-muted-foreground">
                Semua lembaga yang terdaftar di SantriQ.
            </p>
        </div>

        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Subdomain</th>
                        <th class="px-4 py-3 text-right">Santri</th>
                        <th class="px-4 py-3 text-right">Pengajar</th>
                        <th class="px-4 py-3 text-right">Wali</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="tenant in tenants"
                        :key="tenant.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link :href="show(tenant.id)" class="hover:underline">
                                {{ tenant.name }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ tenant.subdomain }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ tenant.students_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ tenant.teachers_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ tenant.guardians_count }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="tenant.suspended_at ? 'destructive' : 'secondary'"
                            >
                                {{ tenant.suspended_at ? 'Disuspend' : 'Aktif' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                as-child
                                :variant="tenant.suspended_at ? 'default' : 'destructive'"
                                size="sm"
                            >
                                <Link :href="toggleStatus(tenant.id)" method="patch">
                                    {{ tenant.suspended_at ? 'Aktifkan' : 'Suspend' }}
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="tenants.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada lembaga terdaftar.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
```

- [ ] **Step 4: Create `SuperAdmin/Show.vue`**

`resources/js/pages/SuperAdmin/Show.vue`:

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import { index, toggleStatus } from '@/routes/super-admin';
import type { Tenant, User } from '@/types/auth';

type TenantDetail = Tenant & {
    students_count: number;
    teachers_count: number;
    guardians_count: number;
};

defineProps<{
    tenant: TenantDetail;
    staff: User[];
}>();

defineOptions({
    layout: SuperAdminLayout,
});
</script>

<template>
    <Head :title="`Lembaga: ${tenant.name}`" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <Link
                    :href="index()"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    &larr; Kembali ke daftar lembaga
                </Link>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ tenant.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ tenant.subdomain }}
                </p>
            </div>
            <Button
                as-child
                :variant="tenant.suspended_at ? 'default' : 'destructive'"
            >
                <Link :href="toggleStatus(tenant.id)" method="patch">
                    {{
                        tenant.suspended_at
                            ? 'Aktifkan Lembaga'
                            : 'Suspend Lembaga'
                    }}
                </Link>
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-md border bg-card p-4">
                <p class="text-sm text-muted-foreground">Santri</p>
                <p class="text-2xl font-bold">{{ tenant.students_count }}</p>
            </div>
            <div class="rounded-md border bg-card p-4">
                <p class="text-sm text-muted-foreground">Pengajar</p>
                <p class="text-2xl font-bold">{{ tenant.teachers_count }}</p>
            </div>
            <div class="rounded-md border bg-card p-4">
                <p class="text-sm text-muted-foreground">Wali Santri</p>
                <p class="text-2xl font-bold">{{ tenant.guardians_count }}</p>
            </div>
        </div>

        <div>
            <h2 class="mb-2 text-lg font-semibold">Staf Lembaga</h2>
            <div class="overflow-x-auto rounded-md border bg-card">
                <table class="w-full text-left text-sm">
                    <thead
                        class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Peran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="member in staff" :key="member.id">
                            <td class="px-4 py-3 font-medium">
                                {{ member.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ member.email }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary">
                                    {{
                                        member.role === 'admin'
                                            ? 'Admin Lembaga'
                                            : 'Pengajar'
                                    }}
                                </Badge>
                            </td>
                        </tr>
                        <tr v-if="staff.length === 0">
                            <td
                                colspan="3"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                Belum ada staf terdaftar.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 5: Type-check, lint, and run the full backend suite**

Run: `npm run types:check`
Expected: no errors.

Run: `npm run lint`
Expected: no errors.

Run: `php artisan test --compact`
Expected: PASS (full suite, no regressions).

Run: `composer test`
Expected: PASS (config:clear + pint --test + phpstan + pest all green).

- [ ] **Step 6: Manual browser check**

Start the app (`composer dev` or ask the user to), then via a browser (or the Playwright MCP tool):

1. Grant a test user super admin: `php artisan tinker --execute 'App\Models\User::first()->update(["is_super_admin" => true]);'`
2. Log in as that user on their tenant subdomain — confirm "Panel Super Admin" appears in the sidebar footer.
3. Click it — confirm it lands on `/super-admin` on the apex domain, showing the tenant list with correct counts.
4. Click a tenant name — confirm the detail page shows counts and staff.
5. Click "Suspend" — confirm the badge/button flip, then visit that tenant's subdomain in a new tab — confirm it 403s.
6. Click "Aktifkan" — confirm the tenant subdomain is reachable again.
7. Log in as a non-super-admin — confirm the sidebar link is absent and `/super-admin` 403s directly.

- [ ] **Step 7: Commit**

```bash
git add resources/js/layouts/SuperAdminLayout.vue \
        resources/js/pages/SuperAdmin/Index.vue resources/js/pages/SuperAdmin/Show.vue \
        resources/js/routes/super-admin
git commit -m "feat: add super admin panel pages"
```

---

## Provisioning the first super admin (not part of any task — manual, per spec §2)

No UI grants this. After deploying, run once:

```bash
php artisan tinker --execute 'App\Models\User::where("email", "you@example.com")->update(["is_super_admin" => true]);'
```
