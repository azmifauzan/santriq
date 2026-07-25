# Revamp Tema User Panel & Super Admin — Design Spec

## Tujuan

Panel user (`AppLayout`) dan panel super admin (`SuperAdminLayout`) saat ini
memakai palet default shadcn (abu-abu/hitam, `--radius: 0.5rem`) yang tidak
konsisten dengan landing page (`resources/js/pages/Welcome.vue`), yang
memakai palet emerald + latar putih hangat (`#fbfdf9`), radius besar
(`rounded-2xl`/`rounded-3xl`), shadow lembut, dan tombol pill. Spec ini
menyelaraskan seluruh panel dengan bahasa visual landing page.

## Non-tujuan

- Portal wali santri (`resources/js/pages/guardian/*`) — publik via Telegram,
  gaya sengaja dibiarkan berbeda, tidak disentuh.
- Redesign copy/struktur data, hanya visual.
- Halaman auth (`resources/js/pages/auth/*`) tidak didesain ulang detail —
  otomatis mendapat token baru dari perubahan `app.css` tapi tidak masuk fase
  eksekusi manual.

## 1. Design tokens (`resources/css/app.css`)

Ganti nilai HSL di `:root` dan `.dark`. Struktur variabel (`@theme inline`)
tidak berubah, hanya nilainya.

```
:root
  --background: hsl(80 33% 98%)        /* ~#fbfdf9, warm off-white landing */
  --foreground: hsl(222 47% 6%)        /* slate-950 */
  --card: hsl(0 0% 100%)
  --card-foreground: hsl(222 47% 6%)
  --popover: hsl(0 0% 100%)
  --popover-foreground: hsl(222 47% 6%)
  --primary: hsl(160 84% 30%)          /* emerald-600 */
  --primary-foreground: hsl(0 0% 100%)
  --secondary: hsl(210 20% 96%)        /* slate-100 */
  --secondary-foreground: hsl(222 47% 11%)
  --muted: hsl(210 20% 96%)
  --muted-foreground: hsl(215 16% 47%) /* slate-500 */
  --accent: hsl(152 68% 96%)           /* emerald-50 */
  --accent-foreground: hsl(160 84% 24%) /* emerald-700 */
  --destructive: hsl(0 84% 60%)
  --destructive-foreground: hsl(0 0% 98%)
  --border: hsl(160 30% 6% / 8%)       /* emerald-950/8, spt landing */
  --input: hsl(160 10% 88%)
  --ring: hsl(160 84% 30%)
  --chart-1: hsl(160 84% 30%)          /* emerald */
  --chart-2: hsl(38 92% 50%)           /* amber */
  --chart-3: hsl(258 60% 60%)          /* violet, spt kartu pencapaian */
  --chart-4: hsl(199 89% 48%)          /* sky */
  --chart-5: hsl(340 75% 55%)          /* rose */
  --radius: 0.875rem                   /* naik dari 0.5rem */
  --sidebar-background: var(--background)
  --sidebar-foreground: hsl(222 47% 11%)
  --sidebar-primary: hsl(160 84% 30%)
  --sidebar-primary-foreground: hsl(0 0% 100%)
  --sidebar-accent: hsl(152 68% 96%)   /* emerald-50, active item */
  --sidebar-accent-foreground: hsl(160 84% 24%) /* emerald-700 */
  --sidebar-border: hsl(160 30% 6% / 8%)
  --sidebar-ring: hsl(160 84% 30%)
  --sidebar: var(--background)

.dark
  --background: hsl(222 47% 5%)        /* slate-950 */
  --foreground: hsl(0 0% 98%)
  --card: hsl(222 40% 10%)             /* slate-900 */
  --card-foreground: hsl(0 0% 98%)
  --popover: hsl(222 40% 10%)
  --popover-foreground: hsl(0 0% 98%)
  --primary: hsl(160 70% 42%)          /* emerald-500, lebih terang di gelap */
  --primary-foreground: hsl(222 47% 5%)
  --secondary: hsl(222 30% 16%)
  --secondary-foreground: hsl(0 0% 98%)
  --muted: hsl(222 30% 16%)
  --muted-foreground: hsl(215 16% 65%)
  --accent: hsl(160 40% 16%)
  --accent-foreground: hsl(152 68% 85%)
  --destructive: hsl(0 84% 60%)
  --destructive-foreground: hsl(0 0% 98%)
  --border: hsl(0 0% 100% / 10%)       /* white/10, spt landing dark */
  --input: hsl(0 0% 100% / 12%)
  --ring: hsl(160 70% 42%)
  --chart-1..5: sama hue, lightness naik dikit buat kontras gelap
  --sidebar-background: var(--background)
  --sidebar-foreground: hsl(0 0% 90%)
  --sidebar-primary: hsl(160 70% 42%)
  --sidebar-accent: hsl(160 40% 16%)
  --sidebar-accent-foreground: hsl(152 68% 85%)
  --sidebar-border: hsl(0 0% 100% / 10%)
```

Nilai persis boleh disesuaikan kecil saat implementasi untuk kontras AA, tapi
hue/struktur di atas mengikat.

## 2. Layout shell

- `AppSidebar.vue`, `SuperAdminSidebar.vue`: bg ikut token
  `--sidebar-background` (putih hangat), `border-r` pakai
  `--sidebar-border`. Logo (`AppLogo.vue`) dibungkus pill emerald-600 spt
  header landing (`size-9 rounded-xl bg-emerald-600 text-white`).
- Item nav aktif: bg `--sidebar-accent` (emerald-50) + teks
  `--sidebar-accent-foreground` (emerald-700), radius `rounded-xl`. Ini
  sudah jalan otomatis lewat token shadcn sidebar jika `NavMain.vue`/
  `SidebarMenuButton` memakai class `data-[active=true]:bg-sidebar-accent`
  dst — cek implementasi saat ini, sesuaikan bila hardcoded.
- `AppSidebarHeader.vue`: tambahkan `backdrop-blur` + border bawah tipis
  spt nav landing (`border-b bg-background/85 backdrop-blur-xl`).
- `AppShell.vue`/`AppContent.vue`: pastikan bg halaman ikut
  `--background` (bukan putih solid hardcoded).

## 3. Pola komponen (berlaku ke semua halaman list/table)

- **Filter bar**: `rounded-2xl border bg-card p-4` (radius naik dari
  `rounded-lg`).
- **Tabel**: tetap elemen `<table>` native (sudah pakai class token
  `bg-card`/`border`/`text-muted-foreground`/`bg-muted` — TIDAK perlu bikin
  komponen `ui/Table.vue` baru). Wrapper diganti `rounded-2xl`, row hover
  `hover:bg-accent/60` (emerald tint), header row `bg-muted/60`.
- **Badge status**: tetap `rounded-full`, warna semantic (hijau=aktif,
  abu=nonaktif, amber=pending, merah=ditolak) dipertahankan apa adanya —
  bukan brand color, jangan diganti emerald semua.
- **Button**: tidak perlu ubah `Button.vue` per-variant, sudah pakai
  `bg-primary`/`bg-secondary` dll — otomatis ikut token baru. Hanya
  pastikan tidak ada halaman yang hardcode `bg-slate-900`/`bg-black` untuk
  tombol primer.
- **Native `<select>`** (mis. filter kelas di `Students/Index.vue`) diganti
  komponen `ui/select` yang sudah ada di codebase, supaya konsisten dengan
  `Input`.
- **Hero/header section** (mis. gradient di `Dashboard.vue`): ganti gradient
  campuran (`emerald-600 via-teal-600 to-cyan-700`) jadi solid
  `bg-primary` atau gradient emerald tunggal (`from-emerald-600
  to-emerald-700`) — cyan/teal bukan warna landing.
- **Card metrik/statistik**: radius naik ke `rounded-2xl`, shadow
  `shadow-sm hover:shadow-md` dipertahankan, ikon dalam badge bulat
  (`rounded-xl`) warna sesuai chart token (emerald/amber/violet/sky/rose),
  bukan warna Tailwind hardcode langsung.

## 4. Inventori halaman & fase eksekusi

Setiap fase: implementasi lalu verifikasi via `composer dev` + Playwright
screenshot (light & dark) sebelum lanjut fase berikutnya.

**Fase 0 — Fondasi**
`resources/css/app.css`, `AppSidebar.vue`, `AppSidebarHeader.vue`,
`SuperAdminSidebar.vue`, `SuperAdminLayout.vue`, `AppShell.vue`,
`AppContent.vue`, `AppLogo.vue`, `NavMain.vue`, `NavUser.vue`,
`Breadcrumbs.vue`.

**Fase 1 — Operasional inti**
`Dashboard.vue`, `Students/Index.vue`, `Attendance/Index.vue`,
`Attendance/Scan.vue`, `Classrooms/Index.vue`, `Teachers/Index.vue`,
`Guardians/Index.vue`.

**Fase 2 — Transaksional**
`Invoices/Index.vue`, `LeaveRequests/Index.vue`, `Achievements/Index.vue`,
`Reports/Index.vue`, `Students/PrintCards.vue`.

**Fase 3 — Settings & Onboarding**
`settings/Layout.vue`, `settings/Appearance.vue`, `settings/Lembaga.vue`,
`settings/Profile.vue`, `settings/Security.vue`, `Onboarding.vue`.

**Fase 4 — Super admin**
`SuperAdmin/Index.vue`, `SuperAdmin/Show.vue`, `TenantGrowthChart.vue`
(warna chart ikut token `--chart-*` baru).

## 5. Verifikasi

- Visual: `composer dev`, buka tiap halaman via Playwright, screenshot
  light + dark mode, bandingkan konsistensi dengan `Welcome.vue`.
- Otomatis: `composer test` (pint + phpstan + pest) tetap harus hijau —
  perubahan murni frontend Vue/CSS, tidak menyentuh PHP, tapi dijalankan
  untuk memastikan tidak ada regresi tidak sengaja.
- `npm run lint`, `npm run types:check` untuk perubahan `.vue`/`.ts`.
