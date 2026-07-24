# Onboarding Setelah Registrasi/Login Pertama — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** After registration + email verification (or first login for pre-verified Google signups), a tenant's admin user is routed to a standalone onboarding page to fill institution info (address/phone) and landing-page content (tagline, description, hours, accent color, logo, gallery), skippable, before reaching the dashboard.

**Architecture:** A nullable `users.onboarded_at` timestamp gates access. A new `EnsureOnboardingComplete` middleware, added to the existing authenticated tenant route groups, redirects onboarded_at-null admins to a new `OnboardingController`. That controller reuses (via a new shared trait) the same tenant-settings-write logic already used by `Settings\LembagaController`. A new `VerifyEmailResponse` override fixes an existing bug where Fortify's default post-verification redirect targets a path that doesn't exist under subdomain routing, and is the entry point that lands manually-registered admins on onboarding.

**Tech Stack:** Laravel 13, Fortify v1, Inertia v3 + Vue 3, Pest v4, Wayfinder.

## Global Constraints

- Multi-tenant single-DB with `tenant_id` + global scope — never `withoutGlobalScopes()` on a user-facing path.
- FK validation from requests uses `App\Rules\TenantExists::in('table')`, not Laravel's built-in `exists:`.
- Roles: `users.role` (`admin`, `pengajar`) + Policies. No permission package.
- PHP: curly braces always, constructor property promotion, explicit return types, PHPDoc array shapes over inline comments.
- Every change needs a passing Pest test. Run `vendor/bin/pint --dirty --format agent` after any PHP edit.
- Frontend calls routes via Wayfinder (`@/routes`, `@/actions`), never hardcoded URLs.

---

### Task 1: `users.onboarded_at` column, model, factory default

**Files:**
- Create: `database/migrations/2026_07_24_090000_add_onboarded_at_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Modify: `app/Actions/Fortify/CreateNewUser.php:75-83` (the `User::create([...])` call inside the `DB::transaction`)
- Test: `tests/Feature/Auth/RegistrationTest.php`

**Interfaces:**
- Produces: `User::$onboarded_at` (nullable `Carbon`, cast `datetime`). Factory-created users get `onboarded_at => now()` by default (already onboarded — most feature tests assume direct dashboard access). Users created through `CreateNewUser` (real registration, manual or Google) get `onboarded_at => null` explicitly, which later tasks use to gate onboarding.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Auth/RegistrationTest.php`:

```php
test('newly registered admin has not completed onboarding', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

    expect($user->onboarded_at)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="newly registered admin has not completed onboarding"`
Expected: FAIL — `onboarded_at` column doesn't exist yet (query error) or property is undefined.

- [ ] **Step 3: Create the migration**

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
            $table->timestamp('onboarded_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
```

- [ ] **Step 4: Update the `User` model**

In `app/Models/User.php`, add `onboarded_at` to the PHPDoc block right after `@property Carbon|null $email_verified_at`:

```php
 * @property Carbon|null $onboarded_at
```

Add `'onboarded_at' => 'datetime',` to the `casts()` array, alongside `'email_verified_at' => 'datetime',`.

- [ ] **Step 5: Default factory-created users to already onboarded**

In `database/factories/UserFactory.php`, add to the `definition()` return array, alongside `'email_verified_at' => now(),`:

```php
'onboarded_at' => now(),
```

- [ ] **Step 6: Leave real registrations un-onboarded**

In `app/Actions/Fortify/CreateNewUser.php`, inside the `DB::transaction` closure's `User::create([...])` call, add:

```php
'onboarded_at' => null,
```

right after `'role' => 'admin',`. (This is the default for a nullable column with no factory involved, but write it explicitly — it's the whole reason this task exists, and an explicit `null` here reads as intentional rather than an omission.)

- [ ] **Step 7: Run migration and test**

Run: `php artisan migrate`
Run: `php artisan test --compact --filter="newly registered admin has not completed onboarding"`
Expected: PASS

- [ ] **Step 8: Run the full suite to confirm no regressions from the factory default**

Run: `php artisan test --compact`
Expected: PASS (all existing tests unaffected — factory users are onboarded by default)

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_07_24_090000_add_onboarded_at_to_users_table.php app/Models/User.php database/factories/UserFactory.php app/Actions/Fortify/CreateNewUser.php tests/Feature/Auth/RegistrationTest.php
git commit -m "feat: add onboarded_at gate to users"
```

---

### Task 2: Institution address/phone + shared landing-update trait

**Files:**
- Create: `app/Concerns/UpdatesTenantLandingSettings.php`
- Modify: `app/Http/Requests/Settings/LembagaUpdateRequest.php`
- Modify: `app/Http/Controllers/Settings/LembagaController.php`
- Modify: `resources/js/pages/settings/Lembaga.vue`
- Test: `tests/Feature/Settings/LembagaSettingsTest.php`

**Interfaces:**
- Produces: `App\Concerns\UpdatesTenantLandingSettings::applyLandingUpdate(LembagaUpdateRequest $request, Tenant $tenant): void` — validates nothing itself (the request already did), writes `tenant->address`, `tenant->phone`, and merges `tenant->settings['landing']`, then saves. Task 3's `OnboardingController` consumes this exact method.
- Consumes: `App\Http\Requests\Settings\LembagaUpdateRequest` (extended with `address`, `phone` rules).

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Settings/LembagaSettingsTest.php`:

```php
test('admin can update institution address and phone', function () {
    $tenant = Tenant::factory()->create(['address' => 'Alamat lama', 'phone' => '0800000000']);
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->put(route('lembaga.update'), [
        'address' => 'Jl. Merdeka No. 1',
        'phone' => '0812345678',
    ]);

    $response->assertRedirect(route('lembaga.edit'));

    $tenant->refresh();
    expect($tenant->address)->toBe('Jl. Merdeka No. 1');
    expect($tenant->phone)->toBe('0812345678');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="admin can update institution address and phone"`
Expected: FAIL — `address`/`phone` are silently dropped (not in `LembagaUpdateRequest` rules, not written by the controller), so `$tenant->address` stays `'Alamat lama'`.

- [ ] **Step 3: Extend the form request**

In `app/Http/Requests/Settings/LembagaUpdateRequest.php`, add to the `rules()` array, before `'tagline'`:

```php
'address' => ['nullable', 'string', 'max:255'],
'phone' => ['nullable', 'string', 'max:30'],
```

- [ ] **Step 4: Create the shared trait**

```php
<?php

namespace App\Concerns;

use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Models\Tenant;

trait UpdatesTenantLandingSettings
{
    private function applyLandingUpdate(LembagaUpdateRequest $request, Tenant $tenant): void
    {
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

        $tenant->update([
            'address' => $request->filled('address') ? $request->string('address')->toString() : $tenant->address,
            'phone' => $request->filled('phone') ? $request->string('phone')->toString() : $tenant->phone,
            'settings' => [...$tenant->settings ?? [], 'landing' => $landing],
        ]);
    }
}
```

- [ ] **Step 5: Use the trait in `LembagaController`**

Replace the body of `app/Http/Controllers/Settings/LembagaController.php`'s `update()` method. Full file becomes:

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\UpdatesTenantLandingSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LembagaController extends Controller
{
    use UpdatesTenantLandingSettings;

    public function edit(Request $request): Response
    {
        abort_unless($request->user('web')?->isAdmin(), 403);

        $tenant = CurrentTenant::get();

        return Inertia::render('settings/Lembaga', [
            'tenant' => ['address' => $tenant->address, 'phone' => $tenant->phone],
            'landing' => $tenant->settings['landing'] ?? [],
        ]);
    }

    public function update(LembagaUpdateRequest $request): RedirectResponse
    {
        $this->applyLandingUpdate($request, CurrentTenant::get());

        return to_route('lembaga.edit')->with('success', 'Konten landing page diperbarui.');
    }
}
```

- [ ] **Step 6: Add address/phone fields to the settings page**

In `resources/js/pages/settings/Lembaga.vue`, add `tenant` to the `defineProps` type and two new fields. Replace the `defineProps` block:

```ts
defineProps<{
    tenant: {
        address?: string;
        phone?: string;
    };
    landing: {
        tagline?: string;
        description?: string;
        operating_hours?: string;
        accent_color?: string;
        logo_path?: string;
        gallery?: string[];
    };
}>();
```

Insert this block right before the `tagline` field's `<div class="grid gap-2">` (i.e., as the first two fields in the form):

```html
<div class="grid gap-2">
    <Label for="address">Alamat</Label>
    <Input
        id="address"
        name="address"
        :default-value="tenant.address"
        maxlength="255"
    />
    <InputError :message="errors.address" />
</div>

<div class="grid gap-2">
    <Label for="phone">Telepon</Label>
    <Input
        id="phone"
        name="phone"
        :default-value="tenant.phone"
        maxlength="30"
    />
    <InputError :message="errors.phone" />
</div>
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --compact --filter=LembagaSettingsTest`
Expected: PASS (both the new test and the existing landing-content test)

- [ ] **Step 8: Format, typecheck, commit**

```bash
vendor/bin/pint --dirty --format agent
npm run types:check
git add app/Concerns/UpdatesTenantLandingSettings.php app/Http/Requests/Settings/LembagaUpdateRequest.php app/Http/Controllers/Settings/LembagaController.php resources/js/pages/settings/Lembaga.vue tests/Feature/Settings/LembagaSettingsTest.php
git commit -m "feat: add institution address/phone to lembaga settings"
```

---

### Task 3: `OnboardingController`, routes, and the `Onboarding.vue` wizard

**Files:**
- Create: `app/Http/Controllers/OnboardingController.php`
- Modify: `routes/tenant.php`
- Create: `resources/js/pages/Onboarding.vue`
- Test: `tests/Feature/OnboardingTest.php`

**Interfaces:**
- Consumes: `App\Concerns\UpdatesTenantLandingSettings::applyLandingUpdate()` (Task 2), `App\Http\Requests\Settings\LembagaUpdateRequest` (Task 2), `App\Support\CurrentTenant::get()`.
- Produces: named routes `onboarding.show` (GET), `onboarding.update` (PUT), `onboarding.skip` (POST) — Task 4's middleware redirects to `onboarding.show` by name.

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Models\Tenant;
use App\Models\User;

test('admin without onboarding sees the onboarding page', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->get(route('onboarding.show'))->assertOk();
});

test('already onboarded admin visiting onboarding is redirected to dashboard', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)->get(route('onboarding.show'))
        ->assertRedirect(route('dashboard'));
});

test('pengajar cannot view onboarding', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'onboarded_at' => null]);

    $this->actingAsStaff($pengajar)->get(route('onboarding.show'))->assertForbidden();
});

test('completing onboarding saves tenant data and marks the user onboarded', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $response = $this->actingAsStaff($admin)->put(route('onboarding.update'), [
        'address' => 'Jl. Merdeka No. 1',
        'phone' => '0812345678',
        'tagline' => 'Belajar bersama',
    ]);

    $response->assertRedirect(route('dashboard'));

    $tenant->refresh();
    expect($tenant->address)->toBe('Jl. Merdeka No. 1');
    expect($tenant->settings['landing']['tagline'])->toBe('Belajar bersama');
    expect($admin->fresh()->onboarded_at)->not->toBeNull();
});

test('completing onboarding with no fields still marks the user onboarded', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->put(route('onboarding.update'), [])
        ->assertRedirect(route('dashboard'));

    expect($admin->fresh()->onboarded_at)->not->toBeNull();
});

test('skipping onboarding marks the user onboarded without touching tenant data', function () {
    $tenant = Tenant::factory()->create(['address' => 'Alamat asli']);
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->post(route('onboarding.skip'))
        ->assertRedirect(route('dashboard'));

    expect($admin->fresh()->onboarded_at)->not->toBeNull();
    expect($tenant->fresh()->address)->toBe('Alamat asli');
});

test('pengajar cannot skip onboarding', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'onboarded_at' => null]);

    $this->actingAsStaff($pengajar)->post(route('onboarding.skip'))->assertForbidden();
});
```

Save as `tests/Feature/OnboardingTest.php`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=OnboardingTest`
Expected: FAIL — `route('onboarding.show')` etc. don't exist (`RouteNotFoundException`).

- [ ] **Step 3: Create the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Concerns\UpdatesTenantLandingSettings;
use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    use UpdatesTenantLandingSettings;

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user?->isAdmin(), 403);

        if ($user->onboarded_at !== null) {
            return to_route('dashboard');
        }

        $tenant = CurrentTenant::get();

        return Inertia::render('Onboarding', [
            'tenant' => ['address' => $tenant->address, 'phone' => $tenant->phone],
            'landing' => $tenant->settings['landing'] ?? [],
        ]);
    }

    public function update(LembagaUpdateRequest $request): RedirectResponse
    {
        $this->applyLandingUpdate($request, CurrentTenant::get());
        $request->user('web')->update(['onboarded_at' => now()]);

        return to_route('dashboard')->with('success', 'Onboarding selesai.');
    }

    public function skip(Request $request): RedirectResponse
    {
        abort_unless($request->user('web')?->isAdmin(), 403);
        $request->user('web')->update(['onboarded_at' => now()]);

        return to_route('dashboard');
    }
}
```

Note: `LembagaUpdateRequest::authorize()` already checks `isAdmin()`, so `update()` 403s a pengajar before the controller body runs — no separate check needed there.

- [ ] **Step 4: Add routes**

In `routes/tenant.php`, insert `use App\Http\Controllers\OnboardingController;` between the existing lines:

```php
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ReportController;
```

Add a new route group right after the `Route::middleware(['auth', EnsureStaffTenantMatchesSubdomain::class])` profile group (the one with `Route::redirect('settings', ...)`) and before the `Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class])` settings group:

```php
    Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class])->group(function () {
        Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::put('onboarding', [OnboardingController::class, 'update'])->name('onboarding.update');
        Route::post('onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
    });
```

(This is a separate group from the dashboard-feature group and the settings group — Task 4 adds a gate middleware to those two, and onboarding's own routes must stay outside that gate to avoid a redirect loop.)

- [ ] **Step 5: Generate Wayfinder routes**

Run: `php artisan wayfinder:generate`

This creates `resources/js/routes/onboarding/index.ts` with `show`, `update`, `skip` functions.

- [ ] **Step 6: Create the Onboarding.vue wizard**

```vue
<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { show, skip, update } from '@/routes/onboarding';

const { tenant, landing } = defineProps<{
    tenant: { address?: string; phone?: string };
    landing: {
        tagline?: string;
        description?: string;
        operating_hours?: string;
        accent_color?: string;
    };
}>();

const step = ref<1 | 2>(1);

const form = useForm({
    address: tenant.address ?? '',
    phone: tenant.phone ?? '',
    tagline: landing.tagline ?? '',
    description: landing.description ?? '',
    operating_hours: landing.operating_hours ?? '',
    accent_color: landing.accent_color ?? '#059669',
    logo: null as File | null,
    gallery: null as File[] | null,
});

function submit() {
    form.transform((data) => ({
        ...data,
        gallery: data.gallery ?? undefined,
    })).put(update.url());
}

function skipOnboarding() {
    form.post(skip.url());
}

function onLogoChange(event: Event) {
    form.logo = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function onGalleryChange(event: Event) {
    form.gallery = Array.from((event.target as HTMLInputElement).files ?? []);
}
</script>

<template>
    <Head title="Selamat Datang" />

    <div
        class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 dark:bg-slate-950"
    >
        <div
            class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="mb-6 flex items-center gap-2">
                <span
                    class="h-1.5 flex-1 rounded-full"
                    :class="step >= 1 ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-800'"
                />
                <span
                    class="h-1.5 flex-1 rounded-full"
                    :class="step >= 2 ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-800'"
                />
            </div>

            <div v-if="step === 1">
                <h1 class="text-xl font-bold">Info Lembaga</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Data ini tampil di halaman landing lembaga Anda.
                </p>

                <div class="mt-6 space-y-4">
                    <div class="grid gap-2">
                        <Label for="address">Alamat</Label>
                        <Input id="address" v-model="form.address" maxlength="255" />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Telepon</Label>
                        <Input id="phone" v-model="form.phone" maxlength="30" />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <button
                        type="button"
                        class="text-sm text-slate-500 hover:underline dark:text-slate-400"
                        @click="skipOnboarding"
                    >
                        Lewati
                    </button>
                    <Button type="button" @click="step = 2">Lanjut</Button>
                </div>
            </div>

            <form v-else @submit.prevent="submit">
                <h1 class="text-xl font-bold">Landing Page</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Konten ini tampil di halaman publik lembaga Anda.
                </p>

                <div class="mt-6 space-y-4">
                    <div class="grid gap-2">
                        <Label for="tagline">Tagline</Label>
                        <Input id="tagline" v-model="form.tagline" maxlength="150" />
                        <InputError :message="form.errors.tagline" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Deskripsi</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            maxlength="2000"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="operating_hours">Jam Operasional</Label>
                        <Input id="operating_hours" v-model="form.operating_hours" />
                        <InputError :message="form.errors.operating_hours" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="accent_color">Warna Aksen</Label>
                        <Input
                            id="accent_color"
                            v-model="form.accent_color"
                            type="color"
                            class="h-10 w-20 cursor-pointer p-1"
                        />
                        <InputError :message="form.errors.accent_color" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="logo">Logo</Label>
                        <Input id="logo" type="file" accept="image/*" @change="onLogoChange" />
                        <InputError :message="form.errors.logo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="gallery">Galeri Foto (maks. 6)</Label>
                        <Input
                            id="gallery"
                            type="file"
                            accept="image/*"
                            multiple
                            @change="onGalleryChange"
                        />
                        <InputError :message="form.errors.gallery" />
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <button
                        type="button"
                        class="text-sm text-slate-500 hover:underline dark:text-slate-400"
                        @click="skipOnboarding"
                    >
                        Lewati
                    </button>
                    <div class="flex gap-2">
                        <Button type="button" variant="outline" @click="step = 1">
                            Kembali
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            Simpan &amp; Selesai
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
```

`resources/js/components/ui/button/index.ts` already defines an `outline` variant (`border bg-background shadow-xs hover:bg-accent ...`), so `variant="outline"` on the "Kembali" button above works as-is.

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --compact --filter=OnboardingTest`
Expected: PASS

- [ ] **Step 8: Format, typecheck, build, commit**

```bash
vendor/bin/pint --dirty --format agent
npm run types:check
npm run build
git add app/Http/Controllers/OnboardingController.php routes/tenant.php resources/js/pages/Onboarding.vue resources/js/routes/onboarding tests/Feature/OnboardingTest.php
git commit -m "feat: add onboarding page (institution info + landing content)"
```

---

### Task 4: `EnsureOnboardingComplete` gate middleware

**Files:**
- Create: `app/Http/Middleware/EnsureOnboardingComplete.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/OnboardingTest.php` (append)

**Interfaces:**
- Consumes: `route('onboarding.show')` (Task 3).
- Produces: nothing consumed by later tasks — this is the terminal gate.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/OnboardingTest.php`:

```php
test('admin without onboarding is redirected from the dashboard', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});

test('admin without onboarding is redirected from settings', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->get(route('lembaga.edit'))
        ->assertRedirect(route('onboarding.show'));
});

test('onboarded admin reaches the dashboard directly', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)->get(route('dashboard'))->assertOk();
});

test('pengajar without onboarded_at still reaches the dashboard', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'onboarded_at' => null]);

    $this->actingAsStaff($pengajar)->get(route('dashboard'))->assertOk();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=OnboardingTest`
Expected: FAIL on the first two new tests — dashboard/settings return 200 instead of redirecting (no gate yet).

- [ ] **Step 3: Create the middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user !== null && $user->isAdmin() && $user->onboarded_at === null && ! $request->routeIs('onboarding.*')) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Wire it into the route groups**

In `routes/tenant.php`, insert `use App\Http\Middleware\EnsureOnboardingComplete;` before the existing line (alphabetically `EnsureO...` precedes `EnsureS...`):

```php
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureStaffTenantMatchesSubdomain;
```

Change the dashboard-feature group's middleware array from:

```php
Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
```

to:

```php
Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class, EnsureOnboardingComplete::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
```

Do the same for the settings group added in Task 2/3's surrounding context — specifically the group containing `settings/security`, `settings/appearance`, and `settings/lembaga` (the one starting `Route::middleware(['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class])->group(function () { Route::delete('settings/profile', ...)`). Leave the onboarding route group itself (Task 3, Step 4) and the profile-only group (`auth` without `verified`) unchanged.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=OnboardingTest`
Expected: PASS

- [ ] **Step 6: Run the full suite**

Run: `php artisan test --compact`
Expected: PASS — factory-created users default to onboarded (Task 1), so no other test should be affected.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Middleware/EnsureOnboardingComplete.php routes/tenant.php tests/Feature/OnboardingTest.php
git commit -m "feat: gate dashboard and settings behind onboarding completion"
```

---

### Task 5: Admin-created pengajar accounts skip onboarding

**Files:**
- Modify: `app/Http/Controllers/TeacherController.php:29-45` (the `store` method)
- Test: `tests/Feature/TeacherControllerTest.php` (or wherever pengajar-creation tests already live — locate with `grep -rl "TeacherController::class\|route('teachers.store')" tests/`)

**Interfaces:**
- Consumes: `User::$onboarded_at` (Task 1).

- [ ] **Step 1: Locate the existing teacher-creation test file**

Run: `grep -rl "teachers.store" tests/`

Read whichever file that returns, to match its existing style (likely uses `$this->actingAsStaff($admin)->post(route('teachers.store'), [...])`).

- [ ] **Step 2: Write the failing test**

Add to that file:

```php
test('admin-created pengajar is immediately onboarded', function () {
    $tenant = \App\Models\Tenant::factory()->create();
    $admin = $this->actingAsStaff(\App\Models\User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $admin->post(route('teachers.store'), [
        'name' => 'Pengajar Baru',
        'email' => 'pengajar-baru@example.com',
        'password' => 'password',
        'role' => 'pengajar',
    ]);

    $pengajar = \App\Models\User::where('email', 'pengajar-baru@example.com')->firstOrFail();
    expect($pengajar->onboarded_at)->not->toBeNull();
});
```

Adjust the `use` imports at the top of the file to match its existing conventions instead of fully-qualifying inline, if that file already imports `Tenant`/`User`.

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --compact --filter="admin-created pengajar is immediately onboarded"`
Expected: FAIL — `onboarded_at` is null (column exists from Task 1, but `TeacherController::store` doesn't set it).

- [ ] **Step 4: Update `TeacherController::store`**

In `app/Http/Controllers/TeacherController.php`, in the `store()` method's `User::create([...])` call, add after `'email_verified_at' => now(),`:

```php
'onboarded_at' => now(),
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter="admin-created pengajar is immediately onboarded"`
Expected: PASS

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/TeacherController.php
git commit -m "feat: mark admin-created pengajar accounts as onboarded"
```

(This task was already partially covered by Task 4's factory default for most tests, but `TeacherController::store` builds a `User` directly, not via the factory, so it needs its own explicit fix.)

---

### Task 6: Fix `VerifyEmailResponse` to redirect to the tenant subdomain

**Files:**
- Create: `app/Http/Responses/VerifyEmailResponse.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Modify: `tests/Feature/Auth/EmailVerificationTest.php`

**Interfaces:**
- Consumes: `route('dashboard', ['subdomain' => ...])` (existing named route).
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Update the existing tests to the expected new behavior**

In `tests/Feature/Auth/EmailVerificationTest.php`, change:

```php
    $response = $this->actingAsStaff($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect('/dashboard?verified=1');
});
```

to:

```php
    $response = $this->actingAsStaff($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', ['subdomain' => $user->tenant->subdomain]));
});
```

(that's the `'email can be verified'` test). And change the `'already verified user visiting verification link is redirected without firing event again'` test's assertion from:

```php
    $this->actingAsStaff($user)->get($verificationUrl)
        ->assertRedirect('/dashboard?verified=1');
```

to:

```php
    $this->actingAsStaff($user)->get($verificationUrl)
        ->assertRedirect(route('dashboard', ['subdomain' => $user->tenant->subdomain]));
```

Leave the other tests in that file (`'verified user is redirected to dashboard from verification prompt'` and the invalid-hash/invalid-id tests) untouched — they exercise the verification *prompt* controller and Fortify's own config-based `'/dashboard'` redirect there, which this task doesn't touch.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=EmailVerificationTest`
Expected: FAIL on the two updated tests — actual redirect is still the literal `/dashboard?verified=1` path (vendor default `VerifyEmailResponse`), not the tenant-subdomain URL.

- [ ] **Step 3: Create the response override**

```php
<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        /** @var Request $request */
        /** @var User $user */
        $user = $request->user();

        // Fortify's own verification.verify route has no domain constraint (it
        // matches whatever host the emailed link was clicked on — usually the
        // apex, since that's where registration happened), and its default
        // response redirects to the fixed path config('fortify.home'), which
        // only exists under the tenant subdomain route group. Build the
        // dashboard URL from the user's own tenant instead, same pattern as
        // LoginResponse and RegisterResponse.
        return redirect()->intended(
            route('dashboard', ['subdomain' => $user->tenant->subdomain])
        );
    }
}
```

- [ ] **Step 4: Bind it in `FortifyServiceProvider`**

In `app/Providers/FortifyServiceProvider.php`, insert two new imports to keep the existing alphabetical grouping. Change:

```php
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
```

to:

```php
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\VerifyEmailResponse;
```

and change:

```php
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Features;
```

to:

```php
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Features;
```

Then in `register()`, add the binding alongside the existing two:

```php
$this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
$this->app->singleton(LoginResponseContract::class, LoginResponse::class);
$this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=EmailVerificationTest`
Expected: PASS

- [ ] **Step 6: Run the full suite**

Run: `php artisan test --compact`
Expected: PASS

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Responses/VerifyEmailResponse.php app/Providers/FortifyServiceProvider.php tests/Feature/Auth/EmailVerificationTest.php
git commit -m "fix: redirect verified users to their tenant subdomain dashboard"
```

---

### Task 7: End-to-end coverage for both registration paths

**Files:**
- Test: `tests/Feature/OnboardingTest.php` (append)

**Interfaces:**
- Consumes: everything from Tasks 1, 3, 4, 6 — this task adds no new production code, only tests that exercise the full chain.

- [ ] **Step 1: Write the end-to-end tests**

Append to `tests/Feature/OnboardingTest.php`:

```php
test('manual registration lands on onboarding after email verification', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPA Cahaya',
        'subdomain' => 'tpa-cahaya',
        'name' => 'Admin Cahaya',
        'email' => 'admin-cahaya@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = \App\Models\User::where('email', 'admin-cahaya@example.com')->firstOrFail();

    $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $verifyResponse = $this->get($verificationUrl);
    $verifyResponse->assertRedirect(route('dashboard', ['subdomain' => $user->tenant->subdomain]));

    $this->get($verifyResponse->headers->get('Location'))
        ->assertRedirect(route('onboarding.show'));
});
```

- [ ] **Step 2: Run test to verify it passes**

Run: `php artisan test --compact --filter="manual registration lands on onboarding after email verification"`
Expected: PASS (this is a pure integration check across Tasks 1, 4, and 6 — if it fails, re-check that `EnsureOnboardingComplete` is on the dashboard group and `VerifyEmailResponse` is bound)

- [ ] **Step 3: Run the entire project test suite one more time**

Run: `composer test`
Expected: PASS — this runs `config:clear`, `pint --test`, `phpstan`, and the full `pest` suite together.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/OnboardingTest.php
git commit -m "test: cover full registration-to-onboarding flow"
```

---

## Manual Verification (after all tasks)

Since this changes a first-run UX flow, verify it once in a browser before considering the feature done:

1. `composer dev` (or ensure `npm run dev`/`build` has run so Wayfinder + Vite assets are fresh).
2. Register a brand-new institution at `/register` with a fresh subdomain.
3. Check the verification email (via `php artisan pail` or Mailpit/log driver, whichever this environment uses) and click the link.
4. Confirm you land on the onboarding wizard (not a 404, not the dashboard directly).
5. Fill step 1, click "Lanjut", fill step 2, click "Simpan & Selesai" — confirm redirect to dashboard, and that `settings/lembaga` now shows the saved data.
6. Repeat registration with a second fresh subdomain, this time click "Lewati" on step 1 — confirm immediate redirect to dashboard with no tenant fields touched.
7. Log out and log back in as that same already-onboarded admin — confirm direct dashboard access, no onboarding detour.
