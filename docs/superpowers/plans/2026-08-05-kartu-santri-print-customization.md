# Cetak Kartu Santri — Fix Sidebar & Kustomisasi Tampilan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the print-cards page losing its active sidebar highlight (it opens in a new tab via `window.open`), make better use of on-screen horizontal space, and let admins customize the printed card's columns, accent color, visible fields, and logo via a new Settings page.

**Architecture:** Two independent bug fixes (sidebar active-state matching, screen-only layout width) plus a new per-tenant JSON setting (`tenants.settings['card_print']`) following the existing `settings['landing']` pattern, exposed through a new `Settings\CardPrintController` (mirrors `LembagaController`) and consumed by `StudentController::printCards` when rendering `Students/PrintCards.vue`.

**Tech Stack:** Laravel 13 (PHP 8.5), Pest 4, Inertia v3 + Vue 3, existing shadcn `Input`/`Checkbox`/`Button` components, Wayfinder-generated route helpers (no new dependency).

## Global Constraints

- Foreign-key/reference lookups from request input must use `App\Rules\TenantExists::in()`, not bare `exists:` — not applicable here (no FK input in this feature), noted for awareness only.
- Never `withoutGlobalScopes()` on user-facing routes — not touched by this feature.
- Settings persisted per-tenant belong in `tenants.settings` (JSON), merged non-destructively, following `UpdatesTenantLandingSettings`.
- Every PHP change must pass `vendor/bin/pint --dirty --format agent` before commit.
- Every backend change must be covered by a Pest test.
- Frontend-only changes have no JS test runner in this project — verify with `npm run types:check`, `npm run lint`, and a manual browser check (per `CLAUDE.md`: "use the feature in a browser before reporting the task as complete").
- Do not change the printed (paper) default column count — it stays at 2 unless an admin explicitly changes it via the new settings page.
- Reuse the existing Lembaga logo (`tenants.settings['landing']['logo_path']`) for the card logo — no new upload field.

---

### Task 1: Fix sidebar active state & open print-cards in the same tab

**Files:**
- Modify: `resources/js/components/NavMain.vue:17,27`
- Modify: `resources/js/pages/Students/Index.vue:125-128`

**Interfaces:**
- Consumes: `useCurrentUrl()` composable (`resources/js/composables/useCurrentUrl.ts`), already exposing `isCurrentOrParentUrl`.
- Produces: no new exports — both are leaf-level fixes.

- [ ] **Step 1: Switch `NavMain` to prefix matching**

In `resources/js/components/NavMain.vue`, replace line 17:

```js
const { isCurrentUrl } = useCurrentUrl();
```

with:

```js
const { isCurrentOrParentUrl } = useCurrentUrl();
```

Then replace line 27:

```html
                    :is-active="isCurrentUrl(item.href)"
```

with:

```html
                    :is-active="isCurrentOrParentUrl(item.href)"
```

This is safe because every `mainNavItems` href in `resources/js/components/AppSidebar.vue:37-102` (`/dashboard`, `/scan`, `/attendance`, `/students`, `/classrooms`, `/guardians`, `/achievements`, `/invoices`, `/leave-requests`, `/reports`, `/teachers`) is a distinct, non-overlapping prefix — none is a prefix of another, and none is `/`.

- [ ] **Step 2: Navigate to print-cards in the same tab**

In `resources/js/pages/Students/Index.vue`, replace lines 125-128:

```js
function printSelectedCards() {
    const url = `/students/print-cards?classroom_id=${selectedClassroom.value}`;
    window.open(url, '_blank');
}
```

with:

```js
function printSelectedCards() {
    const url = `/students/print-cards?classroom_id=${selectedClassroom.value}`;
    router.visit(url);
}
```

`router` is already imported at the top of this file (`import { Head, useForm, router } from '@inertiajs/vue3';`), no import change needed.

- [ ] **Step 3: Type-check, lint, and verify manually in the browser**

Run: `npm run types:check && npm run lint`
Expected: no errors.

Then in the browser:
1. Go to `/students`, pick a classroom filter (or leave it blank), click "🖨️ Cetak Kartu QR".
2. Confirm it navigates in the **same tab** (no new tab opens) to `/students/print-cards`.
3. Confirm the sidebar's "Data Santri" item is highlighted as active.
4. Click the browser back button — confirm it returns to `/students` with the sidebar still showing "Data Santri" active.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/NavMain.vue resources/js/pages/Students/Index.vue
git commit -m "fix: keep Data Santri highlighted and navigate to cetak kartu in the same tab"
```

---

### Task 2: Use more on-screen width on the print-cards page

**Files:**
- Modify: `resources/js/pages/Students/PrintCards.vue:26,48`

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing new — visual-only change to an existing leaf page.

- [ ] **Step 1: Widen the container and add larger screen breakpoints**

In `resources/js/pages/Students/PrintCards.vue`, replace line 26:

```html
    <div class="mx-auto max-w-6xl p-6">
```

with:

```html
    <div class="mx-auto max-w-[1600px] p-6">
```

Then replace line 48:

```html
            class="grid grid-cols-2 gap-6 md:grid-cols-3 print:grid-cols-2 print:gap-4 print:p-0"
```

with:

```html
            class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 print:grid-cols-2 print:gap-4 print:p-0"
```

The `print:grid-cols-2` class keeps the printed page unchanged for now — Task 6 replaces it with a dynamic value driven by the new setting.

- [ ] **Step 2: Verify manually in the browser**

Run: `npm run build` (or confirm `composer dev` is already running)

Then open `/students/print-cards` on a wide browser window (≥1600px):
1. Confirm cards now lay out in 4-5 columns instead of leaving empty space on the sides.
2. Open the browser's print preview (`Ctrl+P` / `Cmd+P`) — confirm the print layout still shows 2 columns per row, unchanged.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Students/PrintCards.vue
git commit -m "fix: use more screen width on cetak kartu page"
```

---

### Task 3: Backend — card print settings storage, controller, and routes

**Files:**
- Create: `app/Support/CardPrintSettings.php`
- Create: `app/Concerns/UpdatesTenantCardPrintSettings.php`
- Create: `app/Http/Requests/Settings/UpdateCardPrintSettingsRequest.php`
- Create: `app/Http/Controllers/Settings/CardPrintController.php`
- Modify: `routes/tenant.php:15` (import), `routes/tenant.php:128-129` (routes)
- Test: `tests/Feature/Settings/CardPrintSettingsTest.php`

**Interfaces:**
- Consumes: `App\Support\CurrentTenant::get()`, `App\Models\Tenant` (`settings` JSON cast array).
- Produces:
  - `App\Support\CardPrintSettings::defaults(): array{columns_per_print_row: int, accent_color: string, show_nis: bool, show_classroom: bool, show_gender: bool, show_logo: bool}`
  - `App\Support\CardPrintSettings::resolve(Tenant $tenant): array` — same shape, merging saved settings over defaults. Used by Task 5.
  - Named routes `card-print.edit` (GET `settings/cetak-kartu`) and `card-print.update` (PUT `settings/cetak-kartu`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Settings/CardPrintSettingsTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CardPrintSettings;

test('admin sees default card print settings when none saved', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->get(route('card-print.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/CardPrint')
        ->where('cardPrint', CardPrintSettings::defaults())
        ->where('logoPath', null)
    );
});

test('admin can update card print settings', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $response = $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 3,
        'accent_color' => '#059669',
        'show_nis' => true,
        'show_classroom' => false,
        'show_gender' => true,
        'show_logo' => true,
    ]);

    $response->assertRedirect(route('card-print.edit'));

    $tenant->refresh();
    expect($tenant->settings['card_print'])->toBe([
        'columns_per_print_row' => 3,
        'accent_color' => '#059669',
        'show_nis' => true,
        'show_classroom' => false,
        'show_gender' => true,
        'show_logo' => true,
    ]);
});

test('unchecked boolean fields are saved as false', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 2,
        'accent_color' => '#1e293b',
    ]);

    $tenant->refresh();
    expect($tenant->settings['card_print']['show_nis'])->toBeFalse();
    expect($tenant->settings['card_print']['show_classroom'])->toBeFalse();
    expect($tenant->settings['card_print']['show_gender'])->toBeFalse();
    expect($tenant->settings['card_print']['show_logo'])->toBeFalse();
});

test('update rejects invalid columns_per_print_row', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 5,
        'accent_color' => '#1e293b',
    ])->assertSessionHasErrors('columns_per_print_row');
});

test('update rejects invalid accent_color', function () {
    $tenant = Tenant::factory()->create();
    $admin = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']));

    $admin->put(route('card-print.update'), [
        'columns_per_print_row' => 2,
        'accent_color' => 'not-a-color',
    ])->assertSessionHasErrors('accent_color');
});

test('pengajar cannot update card print settings', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = $this->actingAsStaff(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']));

    $pengajar->put(route('card-print.update'), [
        'columns_per_print_row' => 2,
        'accent_color' => '#1e293b',
    ])->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=CardPrintSettingsTest`
Expected: FAIL — route `card-print.edit` / `card-print.update` don't exist yet (404s), and `App\Support\CardPrintSettings` doesn't exist yet (class-not-found in the first test).

- [ ] **Step 3: Create the defaults/resolve helper**

Create `app/Support/CardPrintSettings.php`:

```php
<?php

namespace App\Support;

use App\Models\Tenant;

class CardPrintSettings
{
    /**
     * @return array{columns_per_print_row: int, accent_color: string, show_nis: bool, show_classroom: bool, show_gender: bool, show_logo: bool}
     */
    public static function defaults(): array
    {
        return [
            'columns_per_print_row' => 2,
            'accent_color' => '#1e293b',
            'show_nis' => true,
            'show_classroom' => true,
            'show_gender' => false,
            'show_logo' => false,
        ];
    }

    /**
     * @return array{columns_per_print_row: int, accent_color: string, show_nis: bool, show_classroom: bool, show_gender: bool, show_logo: bool}
     */
    public static function resolve(Tenant $tenant): array
    {
        return [...self::defaults(), ...($tenant->settings['card_print'] ?? [])];
    }
}
```

- [ ] **Step 4: Create the form request**

Create `app/Http/Requests/Settings/UpdateCardPrintSettingsRequest.php`:

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardPrintSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'columns_per_print_row' => ['required', 'integer', 'in:2,3,4'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'show_nis' => ['sometimes', 'boolean'],
            'show_classroom' => ['sometimes', 'boolean'],
            'show_gender' => ['sometimes', 'boolean'],
            'show_logo' => ['sometimes', 'boolean'],
        ];
    }
}
```

The boolean fields are `sometimes` (not `required`) because unchecked HTML checkboxes are omitted from the submitted form entirely — `$request->boolean(...)` in the next step already treats a missing key as `false`.

- [ ] **Step 5: Create the trait that persists the settings**

Create `app/Concerns/UpdatesTenantCardPrintSettings.php`:

```php
<?php

namespace App\Concerns;

use App\Http\Requests\Settings\UpdateCardPrintSettingsRequest;
use App\Models\Tenant;

trait UpdatesTenantCardPrintSettings
{
    /**
     * @return array{columns_per_print_row: int, accent_color: string, show_nis: bool, show_classroom: bool, show_gender: bool, show_logo: bool}
     */
    private function applyCardPrintUpdate(UpdateCardPrintSettingsRequest $request, Tenant $tenant): array
    {
        $cardPrint = [
            'columns_per_print_row' => $request->integer('columns_per_print_row'),
            'accent_color' => $request->string('accent_color')->toString(),
            'show_nis' => $request->boolean('show_nis'),
            'show_classroom' => $request->boolean('show_classroom'),
            'show_gender' => $request->boolean('show_gender'),
            'show_logo' => $request->boolean('show_logo'),
        ];

        $tenant->update([
            'settings' => [...$tenant->settings ?? [], 'card_print' => $cardPrint],
        ]);

        return $cardPrint;
    }
}
```

- [ ] **Step 6: Create the controller**

Create `app/Http/Controllers/Settings/CardPrintController.php`:

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\UpdatesTenantCardPrintSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCardPrintSettingsRequest;
use App\Support\CardPrintSettings;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardPrintController extends Controller
{
    use UpdatesTenantCardPrintSettings;

    public function edit(Request $request): Response
    {
        abort_unless($request->user('web')?->isAdmin(), 403);

        $tenant = CurrentTenant::get();
        $landing = $tenant->settings['landing'] ?? [];

        return Inertia::render('settings/CardPrint', [
            'cardPrint' => CardPrintSettings::resolve($tenant),
            'logoPath' => $landing['logo_path'] ?? null,
        ]);
    }

    public function update(UpdateCardPrintSettingsRequest $request): RedirectResponse
    {
        $this->applyCardPrintUpdate($request, CurrentTenant::get());

        return to_route('card-print.edit')->with('success', 'Tampilan kartu santri diperbarui.');
    }
}
```

- [ ] **Step 7: Register the routes**

In `routes/tenant.php`, add the import after line 14 (`use App\Http\Controllers\ReportController;`), keeping alphabetical order before `LembagaController`:

```php
use App\Http\Controllers\Settings\CardPrintController;
```

Then, in the settings route group (`routes/tenant.php:115-130`), add after line 129 (`Route::put('settings/lembaga', ...)`):

```php
        Route::get('settings/cetak-kartu', [CardPrintController::class, 'edit'])->name('card-print.edit');
        Route::put('settings/cetak-kartu', [CardPrintController::class, 'update'])->name('card-print.update');
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test --compact --filter=CardPrintSettingsTest`
Expected: all 6 tests PASS.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Support/CardPrintSettings.php app/Concerns/UpdatesTenantCardPrintSettings.php app/Http/Requests/Settings/UpdateCardPrintSettingsRequest.php app/Http/Controllers/Settings/CardPrintController.php routes/tenant.php tests/Feature/Settings/CardPrintSettingsTest.php
git commit -m "feat: add card print settings backend"
```

---

### Task 4: Frontend — Settings > Cetak Kartu Santri page

**Files:**
- Create: `resources/js/pages/settings/CardPrint.vue`
- Modify: `resources/js/layouts/settings/Layout.vue`

**Interfaces:**
- Consumes: `settings/cetak-kartu` routes from Task 3 (Wayfinder-generated `resources/js/routes/card-print/index.ts`, exporting `edit`/`update`, same shape as `resources/js/routes/lembaga/index.ts`), Inertia props `cardPrint: { columns_per_print_row: number; accent_color: string; show_nis: boolean; show_classroom: boolean; show_gender: boolean; show_logo: boolean }` and `logoPath: string | null` from `CardPrintController::edit`.
- Produces: nothing new — leaf settings page.

- [ ] **Step 1: Regenerate Wayfinder routes**

Run: `php artisan wayfinder:generate --with-form`

(`--with-form` matches the `formVariants: true` option already set for the Vite plugin in `vite.config.ts:30-32`, which regenerates the same files automatically on `npm run dev`/`npm run build` — running the Artisan command directly here just makes the files available immediately for the next step.)

Expected: `resources/js/routes/card-print/index.ts` is created, exporting `edit` and `update` with `.form()` helpers (same shape as `resources/js/routes/lembaga/index.ts`).

- [ ] **Step 2: Add the settings nav entry**

In `resources/js/layouts/settings/Layout.vue`, add the import after line 9 (`import { edit as editLembaga } from '@/routes/lembaga';`), keeping alphabetical order before it:

```js
import { edit as editCardPrint } from '@/routes/card-print';
```

Then add to the `sidebarNavItems` array (after the `Lembaga` entry, currently lines 27-30):

```js
    {
        title: 'Cetak Kartu Santri',
        href: editCardPrint(),
    },
```

- [ ] **Step 3: Create the settings page**

Create `resources/js/pages/settings/CardPrint.vue`:

```vue
<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit as editLembaga } from '@/routes/lembaga';
import { update } from '@/routes/card-print';

const props = defineProps<{
    cardPrint: {
        columns_per_print_row: number;
        accent_color: string;
        show_nis: boolean;
        show_classroom: boolean;
        show_gender: boolean;
        show_logo: boolean;
    };
    logoPath: string | null;
}>();

const columns = ref(props.cardPrint.columns_per_print_row);
const accentColor = ref(props.cardPrint.accent_color);
const showNis = ref(props.cardPrint.show_nis);
const showClassroom = ref(props.cardPrint.show_classroom);
const showGender = ref(props.cardPrint.show_gender);
const showLogo = ref(props.cardPrint.show_logo);
</script>

<template>
    <Head title="Kustomisasi Kartu Santri" />

    <SettingsLayout>
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Kustomisasi Kartu Santri"
                description="Atur tampilan kartu QR absensi santri saat dicetak."
            />

            <div class="grid gap-6 lg:grid-cols-[1fr_auto]">
                <Form
                    v-bind="update.form()"
                    v-slot="{ errors, processing }"
                    class="space-y-6"
                >
                    <div class="grid gap-2">
                        <Label for="columns_per_print_row"
                            >Jumlah Kolom Saat Cetak</Label
                        >
                        <select
                            id="columns_per_print_row"
                            name="columns_per_print_row"
                            v-model.number="columns"
                            class="h-9 w-32 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option :value="2">2 kolom</option>
                            <option :value="3">3 kolom</option>
                            <option :value="4">4 kolom</option>
                        </select>
                        <InputError :message="errors.columns_per_print_row" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="accent_color">Warna Aksen</Label>
                        <Input
                            id="accent_color"
                            name="accent_color"
                            type="color"
                            v-model="accentColor"
                            class="h-10 w-20 cursor-pointer p-1"
                        />
                        <InputError :message="errors.accent_color" />
                    </div>

                    <div class="grid gap-3">
                        <p class="text-sm font-medium">
                            Field yang Ditampilkan
                        </p>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_nis"
                                name="show_nis"
                                v-model="showNis"
                            />
                            <Label for="show_nis" class="font-normal"
                                >Tampilkan NIS</Label
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_classroom"
                                name="show_classroom"
                                v-model="showClassroom"
                            />
                            <Label for="show_classroom" class="font-normal"
                                >Tampilkan Kelas</Label
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_gender"
                                name="show_gender"
                                v-model="showGender"
                            />
                            <Label for="show_gender" class="font-normal"
                                >Tampilkan Jenis Kelamin</Label
                            >
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_logo"
                                name="show_logo"
                                v-model="showLogo"
                                :disabled="!logoPath"
                            />
                            <Label for="show_logo" class="font-normal"
                                >Tampilkan Logo Lembaga</Label
                            >
                        </div>
                        <p
                            v-if="!logoPath"
                            class="text-sm text-muted-foreground"
                        >
                            Belum ada logo. Upload dulu di
                            <Link
                                :href="editLembaga()"
                                class="underline"
                            >Settings &rarr; Lembaga</Link
                            >.
                        </p>
                    </div>

                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Simpan
                    </Button>
                </Form>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">
                        Pratinjau
                    </p>
                    <div
                        class="flex h-[280px] w-[220px] flex-col items-center justify-between rounded-xl border-2 bg-white p-4 text-center shadow-sm"
                        :style="{ borderColor: accentColor }"
                    >
                        <div
                            class="mb-2 w-full border-b-2 pb-2"
                            :style="{ borderColor: accentColor }"
                        >
                            <span
                                class="flex items-center justify-center gap-1 text-xs font-bold tracking-wider uppercase"
                                :style="{ color: accentColor }"
                            >
                                <img
                                    v-if="showLogo && logoPath"
                                    :src="`/storage/${logoPath}`"
                                    alt=""
                                    class="h-4 w-4 object-contain"
                                />
                                Nama Lembaga
                            </span>
                            <span
                                class="block text-sm font-semibold text-slate-900"
                                >KARTU PRESENSI SANTRI</span
                            >
                        </div>
                        <div
                            class="flex h-24 w-24 items-center justify-center rounded-md border bg-white text-xs text-slate-400"
                        >
                            QR
                        </div>
                        <div class="w-full border-t pt-2">
                            <h3
                                class="text-base leading-tight font-bold text-slate-900"
                            >
                                Ahmad Fauzi
                            </h3>
                            <div
                                class="mt-1 flex flex-wrap items-center justify-center gap-x-3 font-mono text-xs text-slate-600"
                            >
                                <span v-if="showNis">NIS: 1234567</span>
                                <span v-if="showClassroom">Kelas A</span>
                                <span v-if="showGender">Laki-laki</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SettingsLayout>
</template>
```

- [ ] **Step 4: Format, type-check, and lint**

Run: `npm run format && npm run types:check && npm run lint`
Expected: Prettier reformats the new file's attribute wrapping to match project style; no type or lint errors.

- [ ] **Step 5: Verify manually in the browser**

Run: `npm run build` (or confirm `composer dev` is already running)

Then log in as an admin and go to `/settings/cetak-kartu`:
1. Confirm "Cetak Kartu Santri" appears in the settings sidebar and is highlighted when on this page.
2. Change the accent color — confirm the preview card's border/header color updates live.
3. Toggle NIS/Kelas/Jenis Kelamin checkboxes — confirm the preview footer updates live.
4. Confirm "Tampilkan Logo Lembaga" is disabled with a hint linking to Settings → Lembaga (since no logo is uploaded yet in a fresh tenant).
5. Change the column count to 3, click "Simpan" — confirm a success message appears and reloading the page keeps the saved values (column = 3, accent color, checkboxes).

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/settings/CardPrint.vue resources/js/layouts/settings/Layout.vue
git commit -m "feat: add card print customization settings page"
```

---

### Task 5: Backend — expose card settings from `StudentController::printCards`

**Files:**
- Modify: `app/Http/Controllers/StudentController.php:1-23` (imports), `139-170` (`printCards` method)
- Test: `tests/Feature/MasterDataTest.php`

**Interfaces:**
- Consumes: `App\Support\CardPrintSettings::resolve()` from Task 3.
- Produces: `Inertia::render('Students/PrintCards', [...])` now includes `cardSettings` (shape from `CardPrintSettings::resolve()`) and `logoPath` (`string|null`), consumed by Task 6.

- [ ] **Step 1: Write the failing tests**

In `tests/Feature/MasterDataTest.php`, add the import after line 7 (`use App\Models\User;`):

```php
use App\Support\CardPrintSettings;
```

Then find the existing test (around line 76):

```php
test('print cards page renders SVG for students', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.print-cards', ['ids' => $student->id]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Students/PrintCards')
        ->has('students', 1)
    );
});
```

Replace it with (adds `cardSettings` assertion) and add two new tests right after it:

```php
test('print cards page renders SVG for students', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.print-cards', ['ids' => $student->id]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Students/PrintCards')
        ->has('students', 1)
        ->where('cardSettings', CardPrintSettings::defaults())
        ->where('logoPath', null)
    );
});

test('print cards page reflects saved card print settings', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'landing' => ['logo_path' => 'tenants/1/logo/logo.png'],
            'card_print' => [
                'columns_per_print_row' => 3,
                'accent_color' => '#059669',
                'show_nis' => false,
                'show_classroom' => true,
                'show_gender' => true,
                'show_logo' => true,
            ],
        ],
    ]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.print-cards', ['ids' => $student->id]));

    $response->assertInertia(fn ($page) => $page
        ->component('Students/PrintCards')
        ->where('cardSettings.columns_per_print_row', 3)
        ->where('cardSettings.accent_color', '#059669')
        ->where('cardSettings.show_nis', false)
        ->where('logoPath', 'tenants/1/logo/logo.png')
    );
});

test('print cards page still returns logo path even when show_logo is off', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'landing' => ['logo_path' => 'tenants/1/logo/logo.png'],
            'card_print' => ['show_logo' => false],
        ],
    ]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.print-cards', ['ids' => $student->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('logoPath', 'tenants/1/logo/logo.png')
    );
});
```

Note: the last test intentionally asserts `logoPath` is still returned even when `show_logo` is `false` — the frontend (Task 6) decides whether to render it based on `cardSettings.show_logo`, since the settings preview page (Task 4) also needs the raw path regardless of the toggle state. This keeps `printCards` a plain data-passthrough, with the display decision made once, in the Vue template.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=MasterDataTest`
Expected: the new/modified tests FAIL — `cardSettings` and `logoPath` are not yet present in the Inertia response.

- [ ] **Step 3: Implement**

In `app/Http/Controllers/StudentController.php`, add imports after line 12 (`use App\Models\Student;`):

```php
use App\Support\CardPrintSettings;
use App\Support\CurrentTenant;
```

Then replace the `printCards` method (lines 139-170):

```php
    public function printCards(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);

        $studentIds = array_filter(explode(',', $request->input('ids', '')));

        $query = Student::with('classroom');
        if (! empty($studentIds)) {
            $query->whereIn('id', $studentIds);
        } elseif ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        $tenant = CurrentTenant::get();
        $landing = $tenant->settings['landing'] ?? [];

        $students = $query->get()->map(function (Student $student) use ($tenant) {
            return [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
                'classroom_name' => $student->classroom ? $student->classroom->name : 'Tanpa Kelas',
                'qr_svg' => QrCodeService::generateSvg($student->qr_token, 180),
                'tenant_name' => $tenant->name,
            ];
        });

        return Inertia::render('Students/PrintCards', [
            'students' => $students,
            'cardSettings' => CardPrintSettings::resolve($tenant),
            'logoPath' => $landing['logo_path'] ?? null,
        ]);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=MasterDataTest`
Expected: all tests in `MasterDataTest.php` PASS.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/StudentController.php tests/Feature/MasterDataTest.php
git commit -m "feat: expose card print settings from printCards"
```

---

### Task 6: Frontend — apply card settings on the print page

**Files:**
- Modify: `resources/js/pages/Students/PrintCards.vue` (full rewrite of script + template + style)

**Interfaces:**
- Consumes: `cardSettings` and `logoPath` props from Task 5; `edit` from `resources/js/routes/card-print` (Task 3/4).
- Produces: nothing new — leaf page.

- [ ] **Step 1: Replace the full file**

Replace the entire contents of `resources/js/pages/Students/PrintCards.vue` with:

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit as editCardPrint } from '@/routes/card-print';

interface PrintableStudent {
    id: number;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    classroom_name: string;
    qr_svg: string;
    tenant_name: string;
}

interface CardSettings {
    columns_per_print_row: number;
    accent_color: string;
    show_nis: boolean;
    show_classroom: boolean;
    show_gender: boolean;
    show_logo: boolean;
}

defineProps<{
    students: PrintableStudent[];
    cardSettings: CardSettings;
    logoPath: string | null;
}>();

function triggerPrint() {
    window.print();
}
</script>

<template>
    <Head title="Cetak Kartu QR Santri" />

    <div class="mx-auto max-w-[1600px] p-6">
        <!-- Print Header Action (hidden during print) -->
        <div
            class="mb-6 flex items-center justify-between rounded-lg border bg-muted/40 p-4 print:hidden"
        >
            <div>
                <h1 class="text-xl font-bold">Cetak Kartu QR Absensi Santri</h1>
                <p class="text-sm text-muted-foreground">
                    Total {{ students.length }} kartu siap dicetak. Gunakan
                    kertas A4 / HVS.
                </p>
            </div>
            <div class="flex gap-2">
                <Link
                    :href="editCardPrint()"
                    class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                >
                    ⚙️ Kustomisasi Kartu
                </Link>
                <button
                    @click="triggerPrint"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
                >
                    🖨️ Cetak Kartu Sekarang
                </button>
            </div>
        </div>

        <!-- Cards Grid -->
        <div
            class="card-grid grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 print:gap-4 print:p-0"
            :style="{ '--print-cols': cardSettings.columns_per_print_row }"
        >
            <div
                v-for="s in students"
                :key="s.id"
                class="card flex h-[280px] flex-col items-center justify-between rounded-xl border-2 bg-white p-4 text-center shadow-sm print:break-inside-avoid print:shadow-none"
                :style="{ '--accent': cardSettings.accent_color }"
            >
                <!-- Header -->
                <div class="mb-2 w-full border-b-2 pb-2 card-accent-border">
                    <span
                        class="flex items-center justify-center gap-1 text-xs font-bold tracking-wider uppercase card-accent-text"
                    >
                        <img
                            v-if="cardSettings.show_logo && logoPath"
                            :src="`/storage/${logoPath}`"
                            alt=""
                            class="h-4 w-4 object-contain"
                        />
                        {{ s.tenant_name }}
                    </span>
                    <span class="block text-sm font-semibold text-slate-900">
                        KARTU PRESENSI SANTRI
                    </span>
                </div>

                <!-- QR Code SVG -->
                <div
                    class="my-1 flex h-32 w-32 items-center justify-center rounded-md border bg-white p-1"
                    v-html="s.qr_svg"
                ></div>

                <!-- Footer Info -->
                <div class="w-full border-t pt-2">
                    <h3
                        class="text-base leading-tight font-bold text-slate-900"
                    >
                        {{ s.name }}
                    </h3>
                    <div
                        class="mt-1 flex flex-wrap items-center justify-center gap-x-3 font-mono text-xs text-slate-600"
                    >
                        <span v-if="cardSettings.show_nis">NIS: {{ s.nis }}</span>
                        <span v-if="cardSettings.show_classroom">{{
                            s.classroom_name
                        }}</span>
                        <span v-if="cardSettings.show_gender">{{
                            s.gender === 'L' ? 'Laki-laki' : 'Perempuan'
                        }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card {
    border-color: var(--accent, #1e293b);
}

.card-accent-border {
    border-color: var(--accent, #1e293b);
}

.card-accent-text {
    color: var(--accent, #1e293b);
}

@media print {
    body {
        background-color: white !important;
    }

    .card-grid {
        grid-template-columns: repeat(var(--print-cols), minmax(0, 1fr));
    }
}
</style>
```

Notes on what changed from the previous version:
- Screen grid keeps the wider `lg:grid-cols-4 xl:grid-cols-5` from Task 2 (this replaces the whole file, so it must be carried over).
- `print:grid-cols-2` (static) is replaced by `.card-grid` + `--print-cols` custom property (dynamic, from `cardSettings.columns_per_print_row`) since Tailwind's `print:` variant only supports build-time-known class names, not a runtime value.
- Card border/header colors now read `--accent` instead of the hardcoded `border-slate-300` / `print:border-slate-800` / `text-slate-600`.
- NIS/classroom/gender are conditionally rendered; the footer row switched from `justify-between` to `flex-wrap justify-center gap-x-3` since it may now show 0-3 items instead of always exactly 2.
- Added the "⚙️ Kustomisasi Kartu" link next to the existing print button.

- [ ] **Step 2: Format, type-check, and lint**

Run: `npm run format && npm run types:check && npm run lint`
Expected: Prettier reformats the file's attribute wrapping to match project style; no type or lint errors.

- [ ] **Step 3: Verify manually in the browser end-to-end**

Run: `npm run build` (or confirm `composer dev` is already running)

1. Go to `/settings/cetak-kartu`, set: 3 columns, a distinct accent color (e.g. bright red `#dc2626`), uncheck "Tampilkan Kelas", check "Tampilkan Jenis Kelamin", save.
2. Go to `/students`, click "🖨️ Cetak Kartu QR".
3. Confirm each card's border and header text now show the chosen accent color.
4. Confirm each card's footer shows NIS and Jenis Kelamin but not Kelas.
5. Open print preview (`Ctrl+P` / `Cmd+P`) — confirm cards now lay out 3 per row on paper (matching the saved setting), not the old fixed 2.
6. Click "⚙️ Kustomisasi Kartu" — confirm it navigates (same tab) to `/settings/cetak-kartu`, and the Settings sidebar shows "Cetak Kartu Santri" highlighted.
7. If a logo was uploaded in Settings → Lembaga during earlier manual testing, toggle "Tampilkan Logo Lembaga" on, save, and confirm the logo appears next to the tenant name on each card.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/Students/PrintCards.vue
git commit -m "feat: apply card print customization settings to printed cards"
```

---

## Post-implementation check

Run the full suite once all six tasks are done:

```bash
composer test
npm run types:check
npm run lint
```

Expected: everything green — this repeats the project's `composer ci:check` baseline plus the frontend checks this feature touched.
