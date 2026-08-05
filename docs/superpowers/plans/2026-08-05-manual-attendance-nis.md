# Manual Attendance by NIS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let staff record manual attendance by typing a student's NIS, as an alternative to the existing QR-token manual input on the scan page.

**Architecture:** Extend the existing `POST /attendance/scan` endpoint to accept either `qr_token` or `nis` and look the student up accordingly; all downstream check-in/check-out/dedup/Telegram-notify logic is unchanged since it's keyed off the resolved `$student`. On the frontend, add a small tab toggle above the existing manual-input textbox so staff pick "Token QR" or "NIS" before typing/pasting and submitting.

**Tech Stack:** Laravel 13 (PHP 8.5), Pest 4, Inertia + Vue 3, existing shadcn `Input`/`Button` components (no new dependency).

## Global Constraints

- Foreign-key/reference lookups from request input must respect tenant scoping — this endpoint already scopes `Student` by `tenant_id`; keep both lookup branches scoped the same way.
- No new npm/composer dependencies (no Tabs component needed — plain toggle buttons).
- Every PHP change must pass `vendor/bin/pint --dirty --format agent` before commit.
- Every change must be covered by a Pest test (`tests/Feature/AttendanceTest.php`).

---

### Task 1: Backend — accept `nis` in `POST /attendance/scan`

**Files:**
- Modify: `app/Http/Controllers/AttendanceController.php:31-49` (the `scan()` method's validation + student lookup)
- Test: `tests/Feature/AttendanceTest.php`

**Interfaces:**
- Consumes: existing route `attendance.scan` (`routes/tenant.php:80`), existing `Student` model columns `nis` (string, unique per `tenant_id`) and `qr_token` (string).
- Produces: `scan()` now accepts JSON body `{ qr_token: string }` **or** `{ nis: string }` (exactly one). Response shape (`success`, `action`, `message`, `student`, `time`) is unchanged for both.

- [ ] **Step 1: Write the failing tests**

Add these two tests to `tests/Feature/AttendanceTest.php`, right after the existing `'scan with invalid qr_token returns 404'` test (after line 101):

```php
test('first scan by nis records checked_in_at', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'nis' => $student->nis,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'action' => 'check_in',
        ]);

    $this->assertDatabaseHas('attendances', [
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'date' => now()->format('Y-m-d'),
        'status' => 'hadir',
    ]);
});

test('scan with invalid nis returns 404', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'nis' => 'NIS-DOES-NOT-EXIST',
    ]);

    $response->assertNotFound();
});

test('scan without qr_token or nis returns validation error', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), []);

    $response->assertStatus(422);
});

test('scan by nis does not match a student from another tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
    $studentInOtherTenant = Student::factory()->create(['tenant_id' => $tenantB->id, 'nis' => 'SHARED-NIS']);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'nis' => $studentInOtherTenant->nis,
    ]);

    $response->assertNotFound();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=AttendanceTest`
Expected: the four new tests FAIL — `first scan by nis...` and `scan by nis does not match...` fail because current code only reads `qr_token` (so `nis` is silently ignored and no match is found); `scan without qr_token or nis...` fails because current validation requires `qr_token` unconditionally, but with an empty body Laravel already 422s, so this one may already pass — that's fine, leave it in as a regression guard.

- [ ] **Step 3: Implement the minimal backend change**

In `app/Http/Controllers/AttendanceController.php`, replace lines 35-49:

```php
        $request->validate([
            'qr_token' => ['required', 'string'],
        ]);

        $student = Student::where('tenant_id', Auth::user()->tenant_id)
            ->where('qr_token', $request->input('qr_token'))
            ->where('status', 'active')
            ->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Kode QR tidak dikenali atau santri tidak aktif.',
            ], 404);
        }
```

with:

```php
        $request->validate([
            'qr_token' => ['required_without:nis', 'nullable', 'string'],
            'nis' => ['required_without:qr_token', 'nullable', 'string'],
        ]);

        $studentQuery = Student::where('tenant_id', Auth::user()->tenant_id)
            ->where('status', 'active');

        if ($request->filled('qr_token')) {
            $studentQuery->where('qr_token', $request->input('qr_token'));
            $notFoundMessage = 'Kode QR tidak dikenali atau santri tidak aktif.';
        } else {
            $studentQuery->where('nis', $request->input('nis'));
            $notFoundMessage = 'NIS tidak ditemukan atau santri tidak aktif.';
        }

        $student = $studentQuery->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => $notFoundMessage,
            ], 404);
        }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=AttendanceTest`
Expected: all tests in `AttendanceTest.php` PASS (existing qr_token tests unaffected, four new tests pass).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/AttendanceController.php tests/Feature/AttendanceTest.php
git commit -m "feat: allow manual attendance scan by NIS"
```

---

### Task 2: Frontend — NIS/QR-token tab toggle on the manual input form

**Files:**
- Modify: `resources/js/pages/Attendance/Scan.vue`

**Interfaces:**
- Consumes: `POST /attendance/scan` from Task 1, now accepting `{ qr_token }` or `{ nis }`.
- Produces: no new exports — this is a leaf page component.

- [ ] **Step 1: Generalize `processToken` to accept a payload instead of a bare token**

In `resources/js/pages/Attendance/Scan.vue`, replace the `processToken` function signature and body (currently lines 96-146):

```javascript
async function processToken(qrToken: string) {
    if (isProcessingScan) {
        return;
    }

    isProcessingScan = true;

    try {
        const response = await fetch('/attendance/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ qr_token: qrToken }),
        });
```

with:

```javascript
async function processToken(payload: { qr_token?: string; nis?: string }) {
    if (isProcessingScan) {
        return;
    }

    isProcessingScan = true;

    try {
        const response = await fetch('/attendance/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });
```

Leave the rest of the function body (response handling, `finally` block) untouched.

- [ ] **Step 2: Update the camera call site**

Replace (around line 66):

```javascript
                        if (rawValue) {
                            processToken(rawValue);
                        }
```

with:

```javascript
                        if (rawValue) {
                            processToken({ qr_token: rawValue });
                        }
```

- [ ] **Step 3: Add manual-mode state and update `submitManual`**

Replace the `manualToken` declaration (line 13):

```javascript
const manualToken = ref('');
```

with:

```javascript
const manualToken = ref('');
const manualMode = ref<'qr_token' | 'nis'>('qr_token');
```

Replace `submitManual` (currently lines 149-156):

```javascript
function submitManual() {
    if (!manualToken.value.trim()) {
        return;
    }

    processToken(manualToken.value.trim());
    manualToken.value = '';
}
```

with:

```javascript
function submitManual() {
    const value = manualToken.value.trim();

    if (!value) {
        return;
    }

    processToken({ [manualMode.value]: value });
    manualToken.value = '';
}
```

- [ ] **Step 4: Add the tab toggle and dynamic placeholder in the template**

Replace the manual form block (currently lines 273-284):

```html
                    <form @submit.prevent="submitManual" class="space-y-4">
                        <div>
                            <Input
                                v-model="manualToken"
                                placeholder="Tempel/Ketik token QR Santri..."
                                autofocus
                            />
                        </div>
                        <Button type="submit" class="w-full">
                            Proses Presensi Manual
                        </Button>
                    </form>
```

with:

```html
                    <div class="mb-4 flex gap-2">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="
                                manualMode === 'qr_token'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground hover:bg-muted/80'
                            "
                            @click="manualMode = 'qr_token'"
                        >
                            Token QR
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="
                                manualMode === 'nis'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground hover:bg-muted/80'
                            "
                            @click="manualMode = 'nis'"
                        >
                            NIS
                        </button>
                    </div>

                    <form @submit.prevent="submitManual" class="space-y-4">
                        <div>
                            <Input
                                v-model="manualToken"
                                :placeholder="
                                    manualMode === 'qr_token'
                                        ? 'Tempel/Ketik token QR Santri...'
                                        : 'Ketik NIS Santri...'
                                "
                                autofocus
                            />
                        </div>
                        <Button type="submit" class="w-full">
                            Proses Presensi Manual
                        </Button>
                    </form>
```

- [ ] **Step 5: Build and verify manually in the browser**

Run: `npm run build` (or confirm `composer dev` / `npm run dev` is already running per project convention)

Then in the browser at the tenant's `/attendance/scan` page:
1. Confirm the "Token QR" tab is active by default and the placeholder reads "Tempel/Ketik token QR Santri...".
2. Click "NIS" — confirm it becomes active and the placeholder reads "Ketik NIS Santri...".
3. Type a real student's NIS and submit — confirm a success banner appears with the student's name and check-in time (same as scanning a QR token).
4. Type a nonexistent NIS and submit — confirm the error banner shows "NIS tidak ditemukan atau santri tidak aktif.".
5. Switch back to "Token QR" and confirm pasting a QR token still works as before.

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/Attendance/Scan.vue
git commit -m "feat: add NIS tab to manual attendance input"
```
