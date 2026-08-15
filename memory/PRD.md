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

## Selesai (2026-06-14, Kelola Pengguna + Profil + Hak Akses)
### Kelola Pengguna (server-side)
- `UserController@index` — pencarian (name/username/email/phone/office), filter status, pengurutan whitelist, `paginate()->withQueryString()`, `with('roles:id,name')` (anti N+1).
- `store`/`update`/`destroy` + `StoreUserRequest` (unik username/email `ignore(id)`, `min:8`, kata sandi opsional saat ubah), proteksi hapus akun sendiri.
- `DataTableCard` kini **dua mode**: CLIENT (default) & **SERVER** (`server` + `meta`/`search`/`sort` + emits `update:search|sort|page|perPage`), termasuk skeleton saat `loading`.
- `pages/Users.vue`: debounce 350 ms, `router.get(..., { only: ['users','filters'], preserveState, preserveScroll, replace })`, dialog tambah/ubah (satu form), `ConfirmDeleteDialog`, filter status.
- Seeder menambah 24 pengguna contoh via `UserFactory` (locale id_ID) agar paginasi & pencarian dapat dicoba.

### Profil tersimpan ke database
- `ProfileController@update` (`UpdateProfileRequest`) & `@updatePassword` (`UpdatePasswordRequest`, aturan `current_password` + `confirmed` + `Password::min(8)`), flash → toast.
- `pages/Profile.vue` memakai `useForm` dengan error per field; nama di sidebar ikut berubah setelah simpan.

### Hak akses menu
- Izin: `dashboard.view`, `users.view`, `users.manage`, `profile.view` — semuanya dimiliki role Super Admin.
- Middleware alias spatie (`role`, `permission`, `role_or_permission`) didaftarkan di `bootstrap/app.php`; setiap rute dilindungi `permission:*` → **otorisasi ditegakkan di backend (403)**, bukan hanya disembunyikan.
- `navigation.js` memberi `perm` per item; `AppSidebar` menyaring memakai `auth.user.permissions`. Item "Kelola Pengguna" berada di area **Administrator**.

### Hasil uji iterasi 3 (`/app/test_reports/iteration_3.json`)
- Backend 100% (CRUD, profil, kata sandi, gate 403). Frontend awalnya ~85% karena 3 bug — **semuanya sudah diperbaiki & diverifikasi ulang**:
  1. `Select.vue` tidak meneruskan atribut (SelectRoot tanpa DOM node) → `inheritAttrs: false` + `v-bind="$attrs"` pada `SelectTrigger`.
  2. `SelectItem` dengan value `''` dilarang reka-ui → sentinel `'all'` di UI, dipetakan ke `''` saat query (filter status kini bisa direset; error konsol hilang).
  3. Tabrakan `data-testid="users-page"` → wrapper halaman menjadi `users-page-view`.

## Selesai (2026-06-15, Penyimpanan + Combobox + DatePicker + urutan menu)
- **Urutan menu area Administrator** sesuai permintaan: Kelola Peranan → Kelola Pengguna → Penampilan → Penyimpanan → Log Aktivitas.
- **Halaman Penyimpanan** (`/storage-settings`, izin `storage.view`/`storage.manage`): driver aktif (local/s3), endpoint, access key, secret (write-only, tidak pernah dikirim balik), region, bucket, path-style toggle, URL publik opsional, dan tombol **Uji Koneksi** (tulis+hapus berkas uji) — terverifikasi BERHASIL ke `https://nos.wjv-1.neo.id` bucket `bpr-assets`.
- Nilai default S3 diisi lewat **seeder** (`Setting::firstOrCreate`, idempoten) sesuai kredensial yang diberikan user.
- `App\Support\FileStorage` — satu pintu unggahan; **avatar & aset merek kini benar-benar tersimpan ke object storage** saat driver `s3` (terverifikasi: avatar tersimpan di `bpr-assets/avatars/...` dan dapat diakses publik HTTP 200). Paket `league/flysystem-aws-s3-v3` ditambahkan.
- **Combobox** (`components/ui/Combobox.vue`) — select DENGAN pencarian (Popover + filter + ikon centang), API sama dengan Select lama. `Select.vue` DIHAPUS; seluruh pemakaian (DataTableCard page-size, filter status pengguna, peranan di dialog, filter modul log, Penampilan) sudah memakai Combobox.
- **DatePicker** (`components/ui/DatePicker.vue`) — kalender compact tanpa dependensi (Senin awal pekan, Hari ini/Bersihkan, nilai `YYYY-MM-DD`). Komponen siap pakai; belum ada field tanggal di halaman yang ada, jadi belum dipasang di mana pun.

## Selesai (2026-06-15, rename menu + hapus deskripsi kartu + audit kode)
- **Nama & urutan menu**: Penampilan → Penyimpanan → Peranan → Pengguna → Log Aktivitas (breadcrumb, judul `Head`, dan judul kartu ikut disesuaikan; "Profil Pengguna" → "Profil").
- **Semua deskripsi di header kartu DIHAPUS** (`CardDescription` tidak dipakai lagi di seluruh halaman) termasuk deskripsi di header dialog. Prop `description` pada `DataTableCard` dan `Dialog` ikut dihapus agar tidak ada kode mati.
- Uji iterasi 5 (Penyimpanan/Combobox/S3): **100% backend & frontend**.

### Audit kode terhadap prinsip yang diminta
Diterapkan:
- **DRY** — `App\Support\TableQuery` (parsing query + meta paginasi) dipakai UserController & ActivityLogController; composable `useServerTable` (debounce/urut/paginasi/filter) dipakai halaman Pengguna & Log Aktivitas; `App\Support\FileStorage` satu pintu unggahan; `Modules` satu sumber izin; `Branding` satu sumber branding; `constants/labels.js` satu leksikon aksi.
- **KISS & YAGNI** — menghapus `Select.vue`, prop `description` yang tak terpakai, `@tanstack/vue-table` & `vue-sonner` yang tidak dipakai; Combobox & DatePicker dibuat sendiri (Popover + logika kecil) alih-alih menambah dependensi berat; tanpa Pinia dan tanpa TypeScript karena belum dibutuhkan.
- **SOLID & Separation of Concerns** — validasi di Form Request, otorisasi di middleware/permission, transformasi data di controller, logika UI di composable, primitive UI murni presentasional; `RoleController::syncMatrix` memakai transaksi DB.
- **Composition over Inheritance** — Vue: `<script setup>` + composables + slot (`cell-<key>`, `header-action`, `filters`); PHP: kelas pembantu di `app/Support` alih-alih hierarki warisan.
- **High Cohesion, Low Coupling** — `DataTableCard` tidak tahu sumber datanya (mode client/server via props+emits); halaman tidak tahu cara request dilakukan (useServerTable).
- **Clean Architecture secukupnya** — hanya lapisan `app/Support` yang tipis; TANPA repository/service ceremony.

Sengaja TIDAK diterapkan (dengan alasan):
- **Feature-based folder di frontend** — tetap berbasis tipe (`pages`/`components`/`composables`) agar setia pada referensi FlowDesk; baru relevan bila modul mencapai puluhan.
- **TypeScript & Pinia** — lihat keputusan sebelumnya (kesetiaan ke FlowDesk; belum ada state lintas halaman).

Backlog kualitas: Pest feature test (auth, CRUD, otorisasi), ESLint + Prettier, PHPStan/Larastan.

## Selesai (2026-06-15, filter tanggal log + hapus log + badge solid)
- **Filter rentang tanggal** di Log Aktivitas memakai `DatePicker` (Dari/Sampai tanggal, `whereDate` inklusif di server) + tombol "Semua tanggal" untuk mereset.
- **Hapus log berdasarkan rentang tanggal**: tombol "Hapus Log" → dialog berisi dua DatePicker + ringkasan rentang; validasi `after_or_equal`; penghapusan itu sendiri ikut tercatat sebagai log (level danger). Izin baru **`activity.manage`** (rute `DELETE /activity`), sudah disinkron ke role Super Admin lewat seeder.
- **Badge lebih solid**: `.state-chip` kini isian penuh `hsl(var(--chip))` dengan teks kontras `hsl(var(--background))`; varian `secondary` pada `Badge` menjadi solid (`bg-foreground/85 text-background`), varian lembut lama tetap tersedia sebagai `muted`.
- Terverifikasi di tema terang & gelap tanpa error console.

## Revisi (2026-06-15, Badge dikembalikan ke shadcn/ui asli)
Atas permintaan user: **semua style badge kustom DIHAPUS**.
- `Badge.vue` = cva shadcn/ui asli (`default`, `secondary`, `destructive`, `outline`); hanya padding disesuaikan compact (`px-2 py-0.5 text-xs`). Tidak ada palet warna, tidak ada variant `chip`/`muted`.
- `app.css`: token `--bdg-*` dan class `.bdg-solid/.bdg-light/.bdg-outline/.bdg-c-*` serta `.state-chip` **dihapus**.
- `StateChip.vue` memetakan token status `--st-*` → variant shadcn (done/progress→`default`, pending→`outline`, draft/cancelled/archived→`secondary`, overdue→`destructive`). `ActivityLog::LEVEL_CHIPS` kembali ke token `--st-*`.
- Uji iterasi 7 (frontend 100%): kontras WCAG jauh di atas AA di kedua tema, tidak ada sisa class lama di DOM, tanpa error console.
- Catatan dari tester: rute dashboard adalah `/` (tidak ada `/dashboard`).

## Perbaikan (2026-06-15, kontras teks chip status/level)
- **Bug**: pada tema terang, badge LEVEL/STATUS (StateChip) berlatar warna solid tapi teksnya gelap → hampir tak terbaca. RCA: class komponen `.state-chip` kalah spesifisitas dengan utility `text-foreground` dari `Badge variant="outline"` (layer utilities selalu menang atas layer components).
- **Fix**: `Badge` mendapat variant `chip` (`bg-[hsl(var(--chip))] text-[hsl(var(--background))]`, semua utility → deterministik); `StateChip` memakai `variant="chip"`; blok CSS `.state-chip` dihapus (tak ada kode mati).
- Terverifikasi testing agent (iterasi 6, frontend 100%): terang `Info` teks putih di atas biru, gelap `Info` teks gelap di atas biru muda; halaman Pengguna ikut benar; tanpa error console.

## Selesai (2026-06-15, palet Badge ala referensi Tabler)
- `Badge.vue` kini punya **dua mode**: (1) semantik lama (`default`/`secondary`/`destructive`/`muted`/`outline`) — pemakaian existing TIDAK berubah; (2) **palet** lewat prop `color` (blue, azure, indigo, purple, pink, red, orange, yellow, lime, green, teal, cyan, dark, light) dengan gaya `variant="solid|light|outline"`.
- Token palet `--bdg-*` ditambahkan di `app.css` untuk light & dark, plus kelas komponen `.bdg-solid` / `.bdg-light` / `.bdg-outline` (hue via `--bdg`) dan pengecualian kontras `.bdg-c-dark` / `.bdg-c-light`.
- Dukungan ikon: container `gap-1` + `[&>svg]:size-3` (cukup `<Star />` di dalam Badge).
- Catatan penting: nama kelas `bdg-c-*` HARUS literal di JS (map `neutralInkMap`), karena kelas dinamis dibuang oleh purge Tailwind (bug ini sudah terjadi & diperbaiki).
- Verifikasi: warna terhitung benar di kedua tema, halaman Pengguna (badge peran/status) tetap normal.
