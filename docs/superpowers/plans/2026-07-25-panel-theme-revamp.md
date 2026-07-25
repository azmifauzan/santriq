# Panel Theme Revamp Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Retheme user panel (`AppLayout`) and super admin panel (`SuperAdminLayout`) to match the emerald + warm-white visual language of the landing page (`resources/js/pages/Welcome.vue`).

**Architecture:** Almost every panel page already uses semantic Tailwind/shadcn token classes (`bg-card`, `border`, `bg-muted`, `rounded-md`/`rounded-lg` which are mapped to the `--radius` CSS variable). Changing the token values in `resources/css/app.css` therefore re-themes the large majority of the UI automatically. The plan first lands the token change (Phase 0, real risk/impact), then sweeps every remaining page to fix the small number of genuinely hardcoded exceptions found during investigation (Dashboard.vue gradient/card radius, Onboarding.vue slate hardcodes) and to verify (screenshot, light+dark) that every other page inherited the new theme correctly.

**Tech Stack:** Laravel 13 + Inertia v3 + Vue 3 + Tailwind CSS v4 (`@theme inline` token mapping) + Pest v4 + Playwright (MCP) for visual verification.

**Reference spec:** `docs/superpowers/specs/2026-07-25-panel-theme-revamp-design.md`

---

## Investigation summary (read before executing)

A full read of every in-scope page found the codebase is unusually disciplined: filter bars use `rounded-lg border bg-muted/30`, table wrappers use `rounded-md border bg-card`, native `<select>`/modals use `rounded-md`/`rounded-lg border bg-background`, badges use `rounded-full` with semantic (non-brand) colors. All of these classes resolve through the `--radius`/`--background`/`--card`/`--muted` tokens defined in `app.css`, so Phase 0's token change reskins them with **zero page edits**. This is true for: `Students/Index.vue`, `Attendance/Index.vue`, `Attendance/Scan.vue` (except the intentional dark camera viewport), `Classrooms/Index.vue`, `Teachers/Index.vue`, `Guardians/Index.vue`, `Invoices/Index.vue`, `Achievements/Index.vue`, `LeaveRequests/Index.vue`, `Reports/Index.vue`, `SuperAdmin/Index.vue`, `SuperAdmin/Show.vue`, `TenantGrowthChart.vue`, `settings/Lembaga.vue`, `settings/Profile.vue`, `settings/Appearance.vue`, `settings/Security.vue`, `settings/Layout.vue`.

Two pages have genuine hardcoded (non-token) classes that need manual edits:
- `Dashboard.vue`: a `teal`/`cyan` gradient hero (landing has no teal/cyan) and 4 stat cards using the literal (non-token) `rounded-xl` instead of the new larger radius.
- `Onboarding.vue`: hardcoded `bg-slate-50`/`bg-white`/`border-slate-200`/`text-slate-500` instead of `bg-background`/`bg-card`/`border`/`text-muted-foreground`.

`Students/PrintCards.vue` is a printable QR-card layout (ink-economy `slate` borders, `print:` variants) — excluded from theming, printed output should not chase brand color. `guardian/*` (wali portal) and `resources/js/pages/auth/*` are out of scope per the design spec.

Every "sweep & verify" task below still greps the file for the hardcoded patterns found during investigation, so if something was missed it gets caught and fixed, not just assumed fine.

---

## Phase 0 — Design tokens & layout shell

### Task 1: Retheme CSS tokens

**Files:**
- Modify: `resources/css/app.css:92-163`

- [ ] **Step 1: Replace the `:root` and `.dark` blocks**

Replace lines 92–163 of `resources/css/app.css` (the existing `:root { ... }` and `.dark { ... }` blocks) with:

```css
:root {
    --background: hsl(80 33% 98%);
    --foreground: hsl(222 47% 6%);
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(222 47% 6%);
    --popover: hsl(0 0% 100%);
    --popover-foreground: hsl(222 47% 6%);
    --primary: hsl(160 84% 30%);
    --primary-foreground: hsl(0 0% 100%);
    --secondary: hsl(210 20% 96%);
    --secondary-foreground: hsl(222 47% 11%);
    --muted: hsl(210 20% 96%);
    --muted-foreground: hsl(215 16% 47%);
    --accent: hsl(152 68% 96%);
    --accent-foreground: hsl(160 84% 24%);
    --destructive: hsl(0 84% 60%);
    --destructive-foreground: hsl(0 0% 98%);
    --border: hsl(160 30% 6% / 8%);
    --input: hsl(160 10% 88%);
    --ring: hsl(160 84% 30%);
    --chart-1: hsl(160 84% 30%);
    --chart-2: hsl(38 92% 50%);
    --chart-3: hsl(258 60% 60%);
    --chart-4: hsl(199 89% 48%);
    --chart-5: hsl(340 75% 55%);
    --radius: 0.875rem;
    --sidebar-background: hsl(80 33% 98%);
    --sidebar-foreground: hsl(222 47% 11%);
    --sidebar-primary: hsl(160 84% 30%);
    --sidebar-primary-foreground: hsl(0 0% 100%);
    --sidebar-accent: hsl(152 68% 96%);
    --sidebar-accent-foreground: hsl(160 84% 24%);
    --sidebar-border: hsl(160 30% 6% / 8%);
    --sidebar-ring: hsl(160 84% 30%);
    --sidebar: hsl(80 33% 98%);
}

.dark {
    --background: hsl(222 47% 5%);
    --foreground: hsl(0 0% 98%);
    --card: hsl(222 40% 10%);
    --card-foreground: hsl(0 0% 98%);
    --popover: hsl(222 40% 10%);
    --popover-foreground: hsl(0 0% 98%);
    --primary: hsl(160 70% 42%);
    --primary-foreground: hsl(222 47% 5%);
    --secondary: hsl(222 30% 16%);
    --secondary-foreground: hsl(0 0% 98%);
    --muted: hsl(222 30% 16%);
    --muted-foreground: hsl(215 16% 65%);
    --accent: hsl(160 40% 16%);
    --accent-foreground: hsl(152 68% 85%);
    --destructive: hsl(0 84% 60%);
    --destructive-foreground: hsl(0 0% 98%);
    --border: hsl(0 0% 100% / 10%);
    --input: hsl(0 0% 100% / 12%);
    --ring: hsl(160 70% 42%);
    --chart-1: hsl(160 70% 42%);
    --chart-2: hsl(38 92% 58%);
    --chart-3: hsl(258 65% 68%);
    --chart-4: hsl(199 89% 58%);
    --chart-5: hsl(340 75% 62%);
    --sidebar-background: hsl(222 47% 5%);
    --sidebar-foreground: hsl(0 0% 90%);
    --sidebar-primary: hsl(160 70% 42%);
    --sidebar-primary-foreground: hsl(222 47% 5%);
    --sidebar-accent: hsl(160 40% 16%);
    --sidebar-accent-foreground: hsl(152 68% 85%);
    --sidebar-border: hsl(0 0% 100% / 10%);
    --sidebar-ring: hsl(160 70% 42%);
    --sidebar: hsl(222 47% 5%);
}
```

- [ ] **Step 2: Build assets to confirm the CSS compiles**

Run: `npm run build`
Expected: exits 0, no Tailwind/PostCSS errors.

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "style: retheme panel CSS tokens to emerald/warm palette"
```

### Task 2: Round the sidebar logo mark

**Files:**
- Modify: `resources/js/components/AppLogo.vue:9-11`

- [ ] **Step 1: Bump the logo box radius**

In `resources/js/components/AppLogo.vue`, change:

```html
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
```

to:

```html
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground"
    >
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/AppLogo.vue
git commit -m "style: round sidebar logo mark to match landing header"
```

### Task 3: Add blur/border treatment to the panel header

**Files:**
- Modify: `resources/js/components/AppSidebarHeader.vue:18-19`

- [ ] **Step 1: Match the landing nav's translucent header**

In `resources/js/components/AppSidebarHeader.vue`, change:

```html
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
```

to:

```html
    <header
        class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-background/85 px-6 backdrop-blur-xl transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/AppSidebarHeader.vue
git commit -m "style: add sticky blur header to panel matching landing nav"
```

### Task 4: Visual verification of Phase 0

**Files:** none (verification only)

- [ ] **Step 1: Start the dev server in the background**

Run: `composer dev` (background — keep it running for all later verification tasks in this plan)
Expected: Vite + `php artisan serve` + queue worker start without error; note the local URL printed (typically `http://localhost:8000`).

- [ ] **Step 2: Screenshot the user dashboard, light and dark**

Use the Playwright MCP tools: navigate to `/dashboard` (log in first via `/login` using a seeded demo account if the session isn't already authenticated — check `docs/RENCANA-IMPLEMENTASI.md` or `php artisan db:seed --class=DemoTenantSeeder` output for demo credentials if needed), take a screenshot, then toggle dark mode with the `ThemeToggle` control in the header and screenshot again.
Expected: warm white / emerald sidebar and header in light mode, slate-950/emerald-500 in dark mode; no leftover pure-white or pure-black shadcn-gray surfaces.

- [ ] **Step 3: Screenshot the super admin panel, light and dark**

Navigate to `/super-admin` (requires a super-admin user; see `SuperAdminTest.php` for how the test suite creates one, or use `php artisan tinker` only to inspect — do not create records via tinker per project convention, prefer an existing seeded super admin). Screenshot light and dark.
Expected: same token-driven look as the user panel — sidebar/header identical component, just different nav items.

- [ ] **Step 4: Fix any contrast/visual issue found**

If a screenshot shows illegible text (e.g. `--muted-foreground` too light on `--muted` background) or a broken layout, adjust the specific HSL value in `resources/css/app.css` from Task 1 and re-screenshot. Do not proceed to Phase 1 until both panels render cleanly in light and dark.

- [ ] **Step 5: Commit any token fix**

```bash
git add resources/css/app.css
git commit -m "style: fix panel token contrast issue found in visual QA"
```
(Skip this step if Step 4 required no change.)

---

## Phase 1 — Operational core pages

### Task 5: Fix Dashboard.vue hardcoded gradient and card radius

**Files:**
- Modify: `resources/js/pages/Dashboard.vue:76`, `:102-103`, `:124-125`, `:146-147`, `:168-169`

- [ ] **Step 1: Write/confirm the regression test still covers this page**

Run: `php artisan test --compact --filter=DashboardTest`
Expected: PASS (this test checks the page renders with the right props — it must stay green through the markup edit below).

- [ ] **Step 2: Replace the hero gradient**

In `resources/js/pages/Dashboard.vue`, change:

```html
        <div
            class="rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 p-6 text-white shadow-md"
        >
```

to:

```html
        <div
            class="rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 p-6 text-white shadow-md"
        >
```

- [ ] **Step 3: Bump the four stat card radii**

In `resources/js/pages/Dashboard.vue`, there are 4 occurrences of:

```html
            <div
                class="rounded-xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
```

Change each to:

```html
            <div
                class="rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
```

(These are the "Total Santri Aktif", "Hadir Hari Ini", "Tagihan Belum Lunas", and "Pengajuan Izin Pending" cards.)

- [ ] **Step 4: Run the test again**

Run: `php artisan test --compact --filter=DashboardTest`
Expected: PASS

- [ ] **Step 5: Screenshot verification**

Navigate to `/dashboard` via Playwright MCP, screenshot light and dark. Confirm the hero is a solid emerald gradient (no teal/cyan) and stat cards visibly rounder than before.

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/Dashboard.vue
git commit -m "style: replace Dashboard teal/cyan gradient with emerald, round stat cards"
```

### Task 6: Sweep & verify Students, Attendance, Classrooms, Teachers, Guardians

**Files:**
- Verify: `resources/js/pages/Students/Index.vue`
- Verify: `resources/js/pages/Attendance/Index.vue`
- Verify: `resources/js/pages/Attendance/Scan.vue`
- Verify: `resources/js/pages/Classrooms/Index.vue`
- Verify: `resources/js/pages/Teachers/Index.vue`
- Verify: `resources/js/pages/Guardians/Index.vue`

- [ ] **Step 1: Grep the five list pages for hardcoded (non-token) surface colors**

Run:

```bash
grep -nE "bg-(white|slate|gray)-?[0-9]*\b|border-(slate|gray)-[0-9]+|text-(slate|gray)-[0-9]+" resources/js/pages/Students/Index.vue resources/js/pages/Attendance/Index.vue resources/js/pages/Attendance/Scan.vue resources/js/pages/Classrooms/Index.vue resources/js/pages/Teachers/Index.vue resources/js/pages/Guardians/Index.vue
```

Expected: no matches, **except** in `Attendance/Scan.vue` where `bg-slate-900`/`text-slate-400` on the camera viewport (`<div class="relative flex min-h-[300px] ... bg-slate-900 p-4 text-white">`) is intentional (a dark camera-feed background, not a themed surface) — leave that one as-is.

If any *other* match appears, replace it using the ruleset: `bg-white`/`bg-slate-900` (surface) → `bg-card`; `bg-slate-50` (page bg) → `bg-background`; `border-slate-200`/`border-slate-800` → `border`; `text-slate-500`/`text-slate-400` → `text-muted-foreground`.

- [ ] **Step 2: Give table rows an emerald-tinted hover (per design spec §3)**

Each of `Students/Index.vue`, `Attendance/Index.vue`, `Classrooms/Index.vue`, `Teachers/Index.vue`, `Guardians/Index.vue` has one data-row line reading:

```html
                        class="hover:bg-muted/30"
```

Change each to:

```html
                        class="hover:bg-accent/60"
```

`Attendance/Scan.vue` has no table — skip it for this step.

- [ ] **Step 3: Run the relevant feature tests**

Run: `php artisan test --compact --filter=MasterDataTest`
Run: `php artisan test --compact --filter=AttendanceTest`
Run: `php artisan test --compact --filter=TeacherManagementTest`
Expected: all PASS.

- [ ] **Step 4: Screenshot each page**

Via Playwright MCP, navigate to `/students`, `/attendance`, `/scan`, `/classrooms`, `/teachers`, `/guardians` and screenshot each in light mode. Confirm: filter bars and table wrappers show the new rounded corners and warm background, native `<select>` filters match the `Input` field styling, status badges keep their semantic colors (green/amber/red/gray), table rows tint emerald on hover, and no page shows a jarring pure-white or pure-black block that breaks from the rest of the panel.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/Students/Index.vue resources/js/pages/Attendance/Index.vue resources/js/pages/Attendance/Scan.vue resources/js/pages/Classrooms/Index.vue resources/js/pages/Teachers/Index.vue resources/js/pages/Guardians/Index.vue
git commit -m "style: emerald-tint table row hover, fix leftover hardcoded surface colors"
```

---

## Phase 2 — Transactional & reporting pages

### Task 7: Sweep & verify Invoices, Achievements, LeaveRequests, Reports

**Files:**
- Verify: `resources/js/pages/Invoices/Index.vue`
- Verify: `resources/js/pages/Achievements/Index.vue`
- Verify: `resources/js/pages/LeaveRequests/Index.vue`
- Verify: `resources/js/pages/Reports/Index.vue`

- [ ] **Step 1: Grep for hardcoded surface colors**

Run:

```bash
grep -nE "bg-(white|slate|gray)-?[0-9]*\b|border-(slate|gray)-[0-9]+|text-(slate|gray)-[0-9]+" resources/js/pages/Invoices/Index.vue resources/js/pages/Achievements/Index.vue resources/js/pages/LeaveRequests/Index.vue resources/js/pages/Reports/Index.vue
```

Expected: no matches. If any appear, apply the same ruleset as Task 6 Step 1.

- [ ] **Step 2: Give table rows an emerald-tinted hover (per design spec §3)**

Each of `Invoices/Index.vue`, `Achievements/Index.vue`, `LeaveRequests/Index.vue`, `Reports/Index.vue` has one data-row line reading:

```html
                        class="hover:bg-muted/30"
```

Change each to:

```html
                        class="hover:bg-accent/60"
```

- [ ] **Step 3: Run the relevant feature tests**

Run: `php artisan test --compact --filter=SppInvoiceTest`
Run: `php artisan test --compact --filter=AchievementAndReportTest`
Run: `php artisan test --compact --filter=LeaveRequestTest`
Expected: all PASS.

- [ ] **Step 4: Screenshot each page**

Via Playwright MCP, navigate to `/invoices`, `/achievements`, `/leave-requests`, `/reports` and screenshot each in light mode. Confirm the explicit `bg-emerald-600 text-white hover:bg-emerald-700` action buttons in `Invoices/Index.vue` ("Verifikasi Bayar", "Konfirmasi Lunas") and `LeaveRequests/Index.vue` ("Setujui") still read correctly against the new `--primary` (they should — `emerald-600` already equals the new `--primary` value), table rows tint emerald on hover, and modals/filter bars/tables match the rest of the panel.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/Invoices/Index.vue resources/js/pages/Achievements/Index.vue resources/js/pages/LeaveRequests/Index.vue resources/js/pages/Reports/Index.vue
git commit -m "style: emerald-tint table row hover, fix leftover hardcoded surface colors in transactional pages"
```

### Task 8: Confirm Students/PrintCards.vue is intentionally excluded

**Files:**
- Verify: `resources/js/pages/Students/PrintCards.vue`

- [ ] **Step 1: Screenshot the print view**

Navigate to `/students/print-cards` (or the route used to reach it from `Students/Index.vue`) via Playwright MCP and screenshot.
Expected: unchanged — `slate` borders and `print:` variants stay as-is, this is a printable artifact, not a themed panel surface (see spec's "Non-tujuan" section).

- [ ] **Step 2: No commit needed** — this task is verification-only, confirming the exclusion decision, not a code change.

---

## Phase 3 — Settings & Onboarding

### Task 9: Fix Onboarding.vue hardcoded slate classes

**Files:**
- Modify: `resources/js/pages/Onboarding.vue:57-84`, `:109`, `:120`, `:194`

- [ ] **Step 1: Run the existing test first**

Run: `php artisan test --compact --filter=OnboardingTest`
Expected: PASS (baseline before edits).

- [ ] **Step 2: Replace the page background and card surface**

In `resources/js/pages/Onboarding.vue`, change:

```html
    <div
        class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 dark:bg-slate-950"
    >
        <div
            class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
```

to:

```html
    <div
        class="flex min-h-screen items-center justify-center bg-background px-4 py-12"
    >
        <div
            class="w-full max-w-xl rounded-2xl border bg-card p-8 shadow-sm"
        >
```

- [ ] **Step 3: Replace the step-progress bar inactive color**

In `resources/js/pages/Onboarding.vue`, there are 2 occurrences of:

```html
                    :class="
                        step >= 1
                            ? 'bg-emerald-600'
                            : 'bg-slate-200 dark:bg-slate-800'
                    "
```

and

```html
                    :class="
                        step >= 2
                            ? 'bg-emerald-600'
                            : 'bg-slate-200 dark:bg-slate-800'
                    "
```

Change `'bg-slate-200 dark:bg-slate-800'` to `'bg-muted'` in both.

- [ ] **Step 4: Replace remaining slate text classes**

In `resources/js/pages/Onboarding.vue`, there are 2 occurrences of:

```html
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
```

Change both to:

```html
                <p class="mt-1 text-sm text-muted-foreground">
```

And 2 occurrences of:

```html
                        class="text-sm text-slate-500 hover:underline dark:text-slate-400"
```

Change both to:

```html
                        class="text-sm text-muted-foreground hover:underline"
```

- [ ] **Step 5: Run the test again**

Run: `php artisan test --compact --filter=OnboardingTest`
Expected: PASS

- [ ] **Step 6: Screenshot verification**

Navigate to `/onboarding` (as a freshly-onboarded admin — see `OnboardingTest.php` for how the test suite reaches this state) via Playwright MCP, screenshot light and dark. Confirm the card background/border and progress bar now follow the panel theme instead of a separate slate palette.

- [ ] **Step 7: Commit**

```bash
git add resources/js/pages/Onboarding.vue
git commit -m "style: retheme Onboarding.vue to use panel design tokens"
```

### Task 10: Sweep & verify settings pages

**Files:**
- Verify: `resources/js/layouts/settings/Layout.vue`
- Verify: `resources/js/pages/settings/Lembaga.vue`
- Verify: `resources/js/pages/settings/Profile.vue`
- Verify: `resources/js/pages/settings/Appearance.vue`
- Verify: `resources/js/pages/settings/Security.vue`

- [ ] **Step 1: Grep for hardcoded surface colors**

Run:

```bash
grep -nE "bg-(white|slate|gray)-?[0-9]*\b|border-(slate|gray)-[0-9]+|text-(slate|gray)-[0-9]+" resources/js/layouts/settings/Layout.vue resources/js/pages/settings/Lembaga.vue resources/js/pages/settings/Profile.vue resources/js/pages/settings/Appearance.vue resources/js/pages/settings/Security.vue
```

Expected: no matches. If any appear, apply the ruleset from Task 6 Step 1.

- [ ] **Step 2: Screenshot each settings page**

Via Playwright MCP, navigate to `/settings/profile`, `/settings/security`, `/settings/appearance`, `/settings/lembaga` and screenshot each in light mode. Confirm the settings sub-nav active-item highlight (`bg-muted` in `settings/Layout.vue:55`) reads clearly against the new `--muted` token, and forms match the panel's input/label styling.

- [ ] **Step 3: Commit (only if Step 1 required a fix)**

```bash
git add resources/js/layouts/settings/Layout.vue resources/js/pages/settings/Lembaga.vue resources/js/pages/settings/Profile.vue resources/js/pages/settings/Appearance.vue resources/js/pages/settings/Security.vue
git commit -m "style: fix leftover hardcoded surface colors in settings pages"
```
(Skip if nothing was changed.)

---

## Phase 4 — Super admin

### Task 11: Sweep & verify super admin pages

**Files:**
- Verify: `resources/js/pages/SuperAdmin/Index.vue`
- Verify: `resources/js/pages/SuperAdmin/Show.vue`
- Verify: `resources/js/components/TenantGrowthChart.vue`
- Verify: `resources/js/layouts/SuperAdminLayout.vue`
- Verify: `resources/js/components/SuperAdminSidebar.vue`

- [ ] **Step 1: Grep for hardcoded surface colors**

Run:

```bash
grep -nE "bg-(white|slate|gray)-?[0-9]*\b|border-(slate|gray)-[0-9]+|text-(slate|gray)-[0-9]+" resources/js/pages/SuperAdmin/Index.vue resources/js/pages/SuperAdmin/Show.vue resources/js/components/TenantGrowthChart.vue resources/js/layouts/SuperAdminLayout.vue resources/js/components/SuperAdminSidebar.vue
```

Expected: no matches. If any appear, apply the ruleset from Task 6 Step 1.

- [ ] **Step 2: Give the tenant table row an emerald-tinted hover (per design spec §3)**

In `resources/js/pages/SuperAdmin/Index.vue`, the tenant row has:

```html
                        class="hover:bg-muted/30"
```

Change to:

```html
                        class="hover:bg-accent/60"
```

`SuperAdmin/Show.vue`'s staff table row has no hover class today — leave it as-is, that's a pre-existing gap unrelated to this theme revamp, not in scope.

- [ ] **Step 3: Run the super admin feature test**

Run: `php artisan test --compact --filter=SuperAdminTest`
Expected: PASS.

- [ ] **Step 4: Screenshot verification**

Via Playwright MCP, navigate to `/super-admin` and a tenant detail page (`/super-admin/tenants/{id}` — check `resources/js/routes/super-admin` for the exact path), screenshot both in light and dark. Confirm the stat tiles, the `TenantGrowthChart` bars (`bg-primary`), the sidebar match the user panel's new emerald/warm look, the tenant table row tints emerald on hover, and that the "Kembali ke Panel Saya" footer link is legible.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/SuperAdmin/Index.vue resources/js/pages/SuperAdmin/Show.vue resources/js/components/TenantGrowthChart.vue resources/js/layouts/SuperAdminLayout.vue resources/js/components/SuperAdminSidebar.vue
git commit -m "style: emerald-tint tenant table hover, fix leftover hardcoded colors in super admin"
```

---

## Phase 5 — Final regression pass

### Task 12: Full test suite, lint, and types

**Files:** none (verification only)

- [ ] **Step 1: Run the full PHP test/quality suite**

Run: `composer test`
Expected: `pint --test`, `phpstan`, and `pest` all pass (no PHP files were touched by this plan, but this confirms no accidental breakage).

- [ ] **Step 2: Run frontend lint and type checks**

Run: `npm run lint`
Run: `npm run types:check`
Expected: both exit 0. Fix any issue raised by the Vue/TS edits in Tasks 5, 9 (and any sweep-task fixes) before proceeding.

- [ ] **Step 3: Stop the dev server**

Stop the `composer dev` background process started in Task 4.

- [ ] **Step 4: Commit any lint/type fix**

```bash
git add -A
git commit -m "fix: address lint/type issues from panel theme revamp"
```
(Skip if Step 2 required no change.)
