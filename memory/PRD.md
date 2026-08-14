# PRD — AdminKit (Starter Kit Panel Admin)

## Problem statement (asli)
"buatkan starterkit admin panel dengan teknologi Backend Laravel 12, PHP 8.2+ / Frontend Vue 3, Inertia.js, TailwindCSS 3, Vite 6. saya melampirkan sistem flowdesk yang saya bangun menggunakan emergent, yang saya inginkan kita akan membuat sebuah admin panel dengan mengikuti design sistem yang ada pada project itu."

Klarifikasi user:
- Mulai dengan **halaman statis** dulu sampai UI disetujui (hindari revisi besar).
- Database: **SQLite**.
- Design system: **ikuti persis FlowDesk**, konsep **compact UI** diutamakan.
- **Dark mode**: ya.
- Lampiran zip FlowDesk = referensi UI (bukan untuk dijalankan); CSS ikut dipelajari, bukan hanya docs.
- Iterasi lanjutan: **hanya menu Dashboard** dulu; sidebar mengikuti FlowDesk (screenshot dilampirkan).

## Arsitektur
- Laravel 12 (PHP 8.2.33) di `/app/adminkit`, SQLite (`database/database.sqlite`).
- Inertia.js 2 + Vue 3.5 + Tailwind 3.4 + Vite 6.4, primitive UI via `reka-ui`, ikon `lucide-vue-next`.
- Referensi FlowDesk disimpan di `/app/reference/flowdesk-frontend` (docs + src + index.css).
- Preview: supervisor `frontend` menjalankan `php artisan serve --port 3000` (script di `/app/frontend/package.json`). Aset di-build (`yarn build`) — jalankan ulang setelah mengubah JS/CSS.
- `trustProxies(at: '*')` agar URL aset memakai https di balik ingress.

## Selesai (2026-06-14)
- Token desain 2 lapis (light+dark) port 1:1 dari `index.css` FlowDesk, termasuk `--st-*`, `--pr-*`, `--chart-*`, density compact, `.tbl-density`, `.form-dense`, `.state-chip`, `.thin-scroll`.
- Primitive Vue: Button, Card(+Header/Title/Description/Content/Footer), Input, Label, Badge, Separator, Skeleton, Progress, Alert, Table set, Avatar, Checkbox, Switch, Select, Dialog, DropdownMenu set, Tooltip set.
- Sistem Sidebar (provide/inject, kuncup ke ikon, sheet mobile, rail, tooltip saat kuncup, state di localStorage).
- Shell: `AppLayout` (header 65px + breadcrumb + lonceng + ModeToggle), `AppSidebar` (area switcher Member/Administrator, grup "Umum", footer dropdown Profil/Keluar), `AuthLayout` split-screen.
- Composite: DataTableCard (cari/urut/paginasi + slot sel), RowActions, StateChip, EmptyState, MiniBarChart, HBarChart (token-only).
- Halaman: **Dashboard** (5 KPI, Pengguna Terbaru + Aktivitas + Penyimpanan tinggi terkunci, Tren Mingguan, Aktivitas per Modul, Sebaran Peranan), **Login** (statis), **Profil** (statis).
- Dark/light/system toggle berfungsi (verifikasi via screenshot).
- README berisi aturan desain yang dikunci.
- Uji frontend (testing agent iterasi 1): 100% lolos, tanpa error console di light & dark.

## Selesai (2026-06-14, lanjutan)
- **Halaman Profil** dibangun ulang mengikuti screenshot referensi: kartu "Informasi Diri"
  (avatar + Unggah Foto di kiri, nama + badge peran di kanan, divider, grid 2 kolom
  Nama/Email/Telepon/Kantor, footer Simpan) dan kartu "Ubah Kata Sandi"
  (Kata Sandi Saat Ini di kolom kiri, lalu Kata Sandi Baru + Konfirmasi, footer Simpan).
- Composite baru `PasswordInput.vue` (toggle mata Eye/EyeOff).
- `config/adminkit.php` demo_user: Zulfadli Rizal · Super Admin · telepon · kantor.

## Backlog
- P0: persetujuan UI dashboard dari user.
- P1: autentikasi nyata (JWT/session Laravel) + migrasi & seeder SQLite; menu Kelola Pengguna.
- P1: Kelola Peranan + matriks hak akses (komponen sudah ada di riwayat, perlu dibuat ulang).
- P2: Kelola Aplikasi, Log Aktivitas, Kelola Keamanan, Kelola Database.
- P2: toast helper (judul baku Sukses/Gagal/Peringatan/Info), ConfirmDeleteDialog, EditableCard.
- P2: skrip design-guard versi Vue.

## Catatan
Seluruh data pada fase ini **DUMMY/MOCKED** (`app/Support/DemoData.php`). Belum ada autentikasi nyata: `/login` hanya redirect ke dashboard.

## Selesai (2026-06-14, autentikasi nyata)
- **spatie/laravel-permission ^6.25** terpasang; role tunggal **"Super Admin"** (enum `App\Enums\RoleName`).
- Migrasi tambahan pada `users`: `username` (unik), `phone`, `office`, `avatar`, `is_active`, `last_login_at`.
- **Auth session guard Laravel** (bukan JWT): `LoginRequest` (Form Request) mendeteksi kredensial email/username/telepon, cek `is_active`, rate limit 5 percobaan per kredensial+IP, `session()->regenerate()`, `redirect()->intended()`; `AuthenticatedSessionController` untuk create/store/destroy.
- Rute dipisah grup middleware `guest` dan `auth`; `throttle:20,1` pada POST /login.
- `HandleInertiaRequests` membagikan `auth.user` lengkap dengan `role`, `roles`, `permissions`, `is_admin`; `flash` sebagai lazy prop.
- Seeder idempoten: Zulfadli Rizal / zulfame / zulfadlirizal@gmail.com / 082320099971 / Pamanukan, kata sandi `password`, role Super Admin.
- **Halaman Login didesain ulang** (di luar design system FlowDesk, sesuai permintaan): layout split "Architectural Split", font display **Playfair Display**, panel kanan berisi **ilustrasi SVG line-art monokrom** (`components/auth/AuthIllustration.vue`) — TANPA gambar raster, animasi float CSS scoped `.auth-*` + `prefers-reduced-motion`.
- **Toast** terpusat: `composables/useToast.js` (judul baku Sukses/Gagal/Peringatan/Info, aksen kiri bertoken) + `components/ui/Toaster.vue` + `composables/useFlashToast.js` (flash server → toast).
- **Composable `useTheme`** menggantikan logika tema di dalam `ModeToggle.vue`.
- **Lazy load halaman** (glob non-eager): bundle awal 479 KB → 273 KB.
- **Laravel Pint** (`pint.json`) dijalankan dan bersih.
- Uji iterasi 2: **12/12 lolos** (`/app/test_reports/iteration_2.json`), tanpa error console. Panduan uji auth: `/app/auth_testing.md`.

## Backlog (diperbarui)
- P1: Kelola Pengguna (CRUD + assign role) memakai `DataTableCard` **mode server-side** (paginasi/filter/sort dari API + debounce).
- P1: Simpan Profil & ubah kata sandi ke database (Form Request + Action + toast).
- P1: Permission granular per modul + Policy/Gate, sidebar menyaring menu berdasarkan permission.
- P2: Halaman Kelola Peranan + matriks hak akses, Log Aktivitas (audit log), Kelola Aplikasi.
- P2: `ConfirmDeleteDialog`, unggah avatar (object storage), lupa kata sandi.
- P2: ESLint + Prettier, Pest feature test untuk auth, PHPStan/Larastan.

## Revisi (2026-06-14, atas permintaan user)
- **Font dikembalikan ke Geist saja** — `Playfair Display` dan `fontFamily.display` dihapus dari `tailwind.config.js` & `app.css`. Satu font untuk seluruh aplikasi.
- **Halaman Login dikembalikan ke design system FlowDesk**: `AuthLayout` split-screen (panel brand `bg-primary` + grid dekoratif + 3 highlight + copyright) dan `Card` (header judul "Masuk", isi `.form-dense`, footer tombol `Masuk` lebar penuh berikon `LogIn`).
- Desain "Architectural Split" beserta `components/auth/AuthIllustration.vue` dan utilitas `.auth-*` **DIHAPUS** (tidak dipakai lagi).
- Error login tampil sebagai `Alert variant="destructive"` (`data-testid="login-form-error"`), kata sandi memakai composite `PasswordInput` (`data-testid="login-password-input"` + `-toggle`).
- Diverifikasi ulang: login via username/email/telepon, error kata sandi salah, toggle kata sandi, `body` font = Geist.
