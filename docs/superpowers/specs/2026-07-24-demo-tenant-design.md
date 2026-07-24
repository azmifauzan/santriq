# Demo Tenant (demo.santriq.web.id)

## Goal

Public demo lembaga at subdomain `demo`, self-resetting hourly, with login
hints on the tenant's login pages so visitors can try admin, pengajar, and
wali (guardian) roles without real credentials or a Telegram account.

## A. Demo tenant & reset

- Reuse the existing `tpq-demo` tenant seeded in `DatabaseSeeder`; change its
  `subdomain` to `demo`.
- Extract the seeding body into `Database\Seeders\DemoDataSeeder`, called
  from both `DatabaseSeeder` and the reset command. Data seeded:
  - 2 classrooms, 5 students each (existing behavior)
  - 1 guardian per student, `telegram_chat_id` left `null` (bypass login
    doesn't need it)
  - Attendances for the last ~14 days per student (mostly `hadir`, a few
    `sakit`/`izin` for realism)
  - A few achievements per student
  - Current-month invoice per student, some marked paid with a `Payment` row
  - 1-2 pending leave requests
- New `DEMO_TENANT_SUBDOMAIN = 'demo'` constant lives in the reset command
  (single hardcoded source of truth, not derived from config/env) so a
  misconfiguration can never point the wipe at a real tenant.
- `php artisan demo:reset`:
  1. Look up tenant by the hardcoded subdomain; abort (no-op) if it doesn't
     exist rather than erroring the schedule.
  2. In a DB transaction, delete all tenant-scoped child rows (attendances,
     achievements, payments, invoices, leave_requests, telegram_messages,
     guardian_student pivot, guardians, students, classrooms) but keep the
     `tenant` row and its two `User` rows (admin/pengajar) — reset their
     `password` to `password` in case a demo visitor changed it.
  3. Run `DemoDataSeeder` for that tenant.
- `routes/console.php`: `Schedule::command('demo:reset')->hourly();`

## B. Wali (guardian) demo login bypass

- `routes/tenant.php`: `POST wali/masuk-demo` →
  `GuardianAuthController::loginDemo()`.
- Controller method: `abort_unless(CurrentTenant::get()->subdomain === 'demo', 404)`,
  then log in as the tenant's first `Guardian` (by `id`) via
  `Auth::guard('guardian')->login(...)`, redirect to
  `guardian.portal.index`. No signature/token needed — this route only ever
  exists for the demo tenant.
- `GuardianAuthController::create()` passes `isDemo` (bool, same subdomain
  check) to `guardian/Login.vue`.
- `guardian/Login.vue`: when `isDemo`, render a "Masuk sebagai Wali Demo"
  button that posts to the bypass route (plain form/button, no phone input
  needed for that path).

## C. Login hints on tenant `demo`

- `FortifyServiceProvider::loginView`: when `$tenant->subdomain === 'demo'`,
  add a `demoHint` prop: `{ admin: {email, password}, pengajar: {email, password} }`.
- `auth/Login.vue`: when `demoHint` is present, render a hint card above/below
  the form with the admin and pengajar credentials (plain text, not
  auto-filled — keeps the form component simple) and a `TextLink` to the wali
  portal login page (`guardian.login`), where the bypass button from section
  B lives.

## D. Infra

- `docker/supervisord.conf`: add `[program:scheduler]` running
  `php artisan schedule:work`, mirroring the existing `queue-worker` program
  block. Requires an image rebuild/redeploy to take effect — not triggered by
  this change alone.

## E. Safety guardrails

- Add `'demo'` to `NotReservedSubdomain::RESERVED` so no real registrant can
  claim it.
- `demo:reset` and `loginDemo()` both gate on the hardcoded subdomain string,
  never on request input — a bug elsewhere can't redirect the wipe or the
  auth bypass at a different tenant.

## Testing

- Feature test: `demo:reset` wipes and reseeds only the demo tenant, leaves
  other tenants untouched, admin/pengajar login still works after reset.
- Feature test: `POST wali/masuk-demo` logs in as a guardian on the demo
  tenant; returns 404 on a non-demo tenant.
- Feature test: `auth/Login` Inertia response includes `demoHint` only for
  the demo tenant.
