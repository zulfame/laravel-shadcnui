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

## Selesai (2026-06-15, matriks hak akses + generator izin + CSV ekspor/impor)
- **Matriks hak akses** (`RoleDetail.vue` + `RoleController::matrix()/syncPermissions()`, `PUT /roles/{role}/permissions`): izin dikelompokkan per entitas (prefix sebelum titik), toggle per entitas & global, pencarian izin, penghitung terpilih. Super Admin read-only (kontrol disabled + 403 server). Diff izin lama→baru tercatat di audit trail + notifikasi `roles.view`.
- **Generator izin standar** (`POST /permissions/generate`, `GeneratePermissionRequest::ABILITIES` = view, view_any, create, update, delete, delete_any): entitas divalidasi huruf kecil, izin yang sudah ada dilewati.
- **Ekspor CSV** mengikuti filter aktif: `/users/export`, `/permissions/export`, `/audit-trail/export` memakai `App\Support\Csv` (streaming + BOM UTF-8); URL dibangun dari state `useServerTable` di frontend (tombol `Button as="a"`).
- **Impor pengguna** (`POST /users/import`, `ImportUserRequest`): kolom `name,username,email,phone,role,password`, validasi per baris memakai `Rules`, baris invalid dilewati, kata sandi kosong → `Str::password(12)`.
- Uji iterasi 21: backend 40/40, frontend 100%. Testing agent menemukan & memperbaiki satu bug: `Rule::in()` dipakai tanpa `use Illuminate\Validation\Rule;` di `UserController::import()`.

## Selesai (2026-06-15, modul Perizinan)
- Modul baru **Perizinan** (`permissions.view` / `permissions.manage`) ditambahkan ke `Modules::MAP` dan di-seed ke Super Admin; menu sidebar diletakkan **tepat di atas Peranan**, breadcrumb + ADMIN_ROUTES diperbarui.
- Halaman `/permissions` (`Permissions.vue`): tabel server-side (cari, sortir nama/guard, filter Entitas dinamis, paginasi), checkbox + hapus massal, dialog Tambah/Ubah dengan validasi format `entitas.aksi` (cermin di UI & `StorePermissionRequest`).
- **Izin inti terkunci**: nama yang ada di `Modules::permissions()` tidak bisa diubah/dihapus (ikon kunci, menu aksi disembunyikan, server menolak 403, hapus massal melewatinya).
- Audit trail + notifikasi bertarget (`permissions.view`) untuk setiap perubahan izin.
- Uji iterasi 20: backend 24/24 dan frontend 100%.
- **Backlog dari referensi UI user (Filament Shield)**: halaman detail peranan akan memakai matriks per entitas dengan aksi View / View Any / Create / Update / Delete / Delete Any, toggle "select all" per entitas dan global, plus kolom Guard — dibangun di atas modul Perizinan ini.

## Selesai (2026-06-15, notifikasi nyata + aksi massal + presisi dialog)
### Notifikasi (sebelumnya 100% DUMMY hardcoded)
- Tabel `notifications` (satu baris per penerima) + model `Notification`, helper `App\Support\Notify::toPermission()/toUser()`.
- **Tepat sasaran**: hanya pengguna AKTIF yang lulus `$user->can($permission)` menerima; **pelaku aksi tidak menerima notifikasi atas aksinya sendiri**. Daftar & jumlah belum dibaca dibagikan lewat `HandleInertiaRequests` selalu dari `Auth::user()`.
- Endpoint `POST /notifications/{id}/read` (403 bila bukan milik pengguna) dan `POST /notifications/read-all`; tombol **Tandai** kini benar-benar menyimpan `read_at` (persisten setelah reload). Klik item menandai dibaca lalu membuka URL tujuan.
- Pemicu: pengguna dibuat/dihapus & hapus massal (`users.view`), peranan dibuat/dihapus & hapus massal (`roles.view`), percobaan masuk gagal (`activity.view`).

### Aksi massal (checkbox)
- `DataTableCard`: prop `selectable` + `selected` + emit `update:selected`, checkbox header (halaman aktif saja) & per baris, bar aksi massal dengan slot `#bulk-actions`.
- Pengguna: `POST /users/bulk` (`delete`/`activate`/`deactivate`) — akun sendiri otomatis dilewati. Peranan: `POST /roles/bulk-destroy` — Super Admin & peranan yang masih dipakai dilewati. Keduanya lewat Form Request (`BulkUserRequest`, `BulkRoleRequest`), tercatat di audit trail (termasuk jumlah dilewati) dan mengirim notifikasi bertarget.

### Presisi header dialog
- `ui/Dialog.vue`: `items-center`, wrapper `space-y-1` dihapus, judul `leading-6`, tombol tutup kotak `grid size-7 place-items-center` (`data-testid=dialog-close`). Δ pusat vertikal judul vs tombol = 0px pada 11 dialog.
- Judul & label sisa dibuat Title Case: `Hapus Log Audit?`, `Hapus Data?`, `Tanggal Awal`, `Tanggal Akhir`.
- Uji iterasi 18 (backend 80/80, frontend 100%) dan iterasi 19 (frontend 100% presisi).

## Selesai (2026-06-15, sidebar collapse + responsif tablet/mobile + README)
- **Bug fatal 1 — sidebar collapse cacat**: brand mark & avatar hilang saat mode ikon karena elemen non-svg tidak punya `shrink-0` (aturan shadcn hanya memberi `shrink-0` pada `svg`) dan tombol `size=lg` menyusut ke 32px. Fix di `AppSidebar.vue`: `shrink-0` pada `BrandMark`/`Avatar` + `group-data-[collapsible=icon]:hidden` pada blok teks & `ChevronsUpDown` (header dan footer).
- **Bug fatal 2 — layout tablet/mobile**: `DataTableCard` kini mendukung `hideBelow: 'sm'|'md'|'lg'|'xl'` (kelas literal `HIDE_BELOW`, bukan dinamis, agar tidak dibuang purge). Dipakai di Pengguna (username `lg`, email `md`, phone `xl`, peranan `sm`) dan Audit Trail (pelaku `md`, modul `sm`); nama pengguna truncate di mobile. Blok identitas Profil menumpuk di mobile; ringkasan AuditDetail 1 kolom di layar kecil.
- `APP_LOCALE=id` + `Carbon::setLocale()` di `AppServiceProvider` → waktu relatif berbahasa Indonesia.
- **README.md lengkap** ditulis (instalasi, struktur, STANDAR VALIDASI wajib, hak akses, audit trail, branding, S3, design system, tabel server-side, daftar rute, deployment, troubleshooting).
- Uji iterasi 17 (frontend 100%): brand mark 32×32 tetap tampil pada keadaan expanded & collapsed (desktop + tablet), tidak ada horizontal overflow pada 390px & 768px di seluruh halaman, kolom tabel muncul/tersembunyi sesuai breakpoint, waktu relatif Indonesia, tanpa error console.

## Selesai (2026-06-15, Audit Trail + bug "kosong tetap kosong")
### Bug: nilai yang dikosongkan kembali ke default
- RCA: middleware `ConvertEmptyStringsToNull` mengubah `''` → `null`, lalu `Branding` menimpa `null` dengan `DEFAULTS`.
- Fix: `Setting::putMany()` menyimpan `''` untuk kunci non-aset (`allowNull: true` khusus aset), `Branding::merged()` memakai default HANYA untuk kunci yang belum pernah diatur, dan fallback turunan `footer_text`/`meta_title` dihapus. Kosong = tetap kosong (Penampilan & Penyimpanan sudah diuji).

### Log Aktivitas → AUDIT TRAIL (rute `/audit-trail`)
- Menu/judul/breadcrumb `Audit Trail`; halaman daftar `AuditTrail.vue`, halaman **detail tersendiri** `AuditDetail.vue` di `/audit-trail/{id}` (dialog dihapus karena data bisa panjang) — berorientasi pengembang: Ringkasan (ID, waktu, ISO 8601, relatif, modul, level+kode, pelaku, email, ID pengguna, objek, kelas objek), tabel **Perubahan Data** lebar penuh (kolom/sebelum/sesudah, monospace, header sticky, max-h 420px + scroll), kartu Kesalahan/Konteks, kartu Teknis, dan **Payload Mentah (JSON)** + tombol Salin.
- Migrasi `2026_08_15_050000_extend_activity_logs_for_audit`: `changes`, `context` (JSON), `method`, `url`, `status_code`, `user_agent`.
- `ActivityLog::record($action, $module, $level, $subject, $changes, $context, $statusCode)` merekam konteks permintaan otomatis. `diffOf($model, $before)` — **`$before` WAJIB `$model->getOriginal()` sebelum `save()`** (bug ini pernah terjadi: old==new). `snapshotOf($model, $deleted)` untuk create/delete. Kolom rahasia (`password`, `s3_secret`, `s3_key`, dll) disamarkan `••••••` via `ActivityLog::MASKED`.
- `Setting::putMany()` mengembalikan diff setelan → dipakai Penampilan & Penyimpanan.
- Pencatatan kegagalan otomatis di `bootstrap/app.php` lewat **`$exceptions->render()`** (bukan `report()`, karena turunan HttpException diabaikan Laravel): 403 → 'Akses ditolak', ≥500 → 'Kegagalan sistem'. `ValidationException` (422) dan 404 sengaja TIDAK dicatat agar tidak bising. Login gagal & lockout dicatat dari `LoginRequest` (level Gagal/Peringatan, status 422/429).
- Uji iterasi 12–16: backend 70/70 dan frontend 100% (diff nyata terverifikasi lewat UI, kata sandi tersamarkan, 403/500 tercatat, 422/404 tidak).

### Catatan operasional
- Branding user sering tereset oleh data uji: nilai benar → `app_name='CODEX'`, `tagline='Core Data Exchange'`, `brand_initials='</>'`, `meta_title='CODEX: Core Data Exchange'` (lalu `php artisan cache:clear`).

## Selesai (2026-06-15, Title Case + revisi Peranan/Pengguna/Penampilan)
- **Title Case** untuk seluruh label, judul kartu, judul dialog, placeholder filter (mis. `Nama Pengguna`, `Kata Sandi`, `Akun Aktif`, `Tambah Pengguna`, `Semua Status`, `Driver Aktif`, `Dari Tanggal`, `Semua Modul`, `Hapus Pengguna?`).
- **Peranan**: card & fungsi `Matriks Hak Akses` DIHAPUS (rute `PUT /roles/matrix`, `SyncMatrixRequest`, kolom Jumlah Izin) — akan dibangun ulang lewat halaman detail. Dialog hanya kolom `Nama Peranan` (tanpa placeholder). Aksi baris: **Detail (ikon mata)** → `GET /roles/{role}` (halaman `RoleDetail.vue`, placeholder), Ubah, lalu `DropdownMenuSeparator` di atas Hapus.
- **Pengguna**: label mengikuti halaman Profil; kolom `Login Terakhir` dipindah menjadi field read-only `Terakhir Login` di dialog; avatar di depan nama dihapus; Peranan tanpa badge & dapat disortir (`sort=role` via subquery roles); Status dapat disortir (`sort=is_active`) dengan badge Aktif=`secondary` (abu) dan Nonaktif=`destructive` (merah); separator di atas Hapus.
- **Wajib vs opsional**: hanya `name`, `role`, `password` wajib. `username` & `email` menjadi **nullable unik**, `phone` **nullable + UNIK** (migrasi `2026_08_15_040000_relax_user_identity_columns`).
- **Penampilan**: uploader `Logo (Latar Terang)`, `Logo (Latar Gelap)`, `Favicon` di card Identitas; card `SEO & Metadata` dan `Open Graph` **digabung** (Meta Title menggantikan OG Title, OG Description memakai Meta Description, toggle `Visibilitas`, kartu pratinjau tetap) — rute `PUT /appearance/og` dihapus.
- **Bug gambar rusak diperbaiki**: nilai aset kini disimpan berawalan disk (`local:branding/x.png` / `s3:...`) di `FileStorage`, plus komponen `BrandMark.vue` & `AssetUploader` jatuh ke inisial/ikon bila berkas gagal dimuat.
- Uji iterasi 10: backend 38/38 dan frontend 100% lolos.

## Selesai (2026-06-15, standar validasi + revisi 4 halaman sesuai referensi)
### Standar validasi (global, WAJIB dipakai untuk form baru)
- `app/Support/Rules.php` = SATU sumber aturan per tipe kolom: `personName()`, `username($ignoreId)`, `email($ignoreId)`, `phone()` (regex `^\+?[0-9]{9,15}$`), `password()`, `text($max)`, `url()`, `slug()`, `path()`, `date()` + `messages()` (pesan Indonesia).
- SETIAP form punya Form Request: `Profile/UpdateProfileRequest|UpdatePasswordRequest|UpdateAvatarRequest`, `User/StoreUserRequest`, `Role/StoreRoleRequest|SyncMatrixRequest`, `Appearance/UpdateIdentityRequest|UpdateSeoRequest|UpdateOgRequest|UpdateContactRequest|UploadAssetRequest`, `StorageSetting/UpdateStorageRequest`, `ActivityLog/DestroyRangeRequest`. Tidak ada lagi `$request->validate()` inline di controller.
- Frontend: `resources/js/lib/validators.js` (cermin aturan backend) + `composables/useLiveValidation.js` (validasi saat blur & sebelum submit, pesan ditulis ke `form.errors`) + `components/ui/PhoneInput.vue` (menolak non-digit, boleh 1 tanda `+`, maks 15 digit). `PasswordInput` meneruskan event `blur`.

### Perubahan halaman (mengikuti gambar referensi user)
- **Login**: judul kartu `Autentikasi`; footer konten hanya "Butuh bantuan? {email dukungan}" (baris copyright dihapus); validasi cepat kolom kredensial & kata sandi.
- **Profil**: judul/breadcrumb `Profil Pengguna`; label `Nama Lengkap`, `Nama Pengguna`, `Alamat Email`, `Nomor HP`; kolom **Kantor dihapus**.
- **Penampilan**: card **Aset Merek DIHAPUS** (logo terang/gelap, thumbnail, warna merek) — favicon dipindah ke card `Identitas Aplikasi`; kolom Perusahaan/Zona Waktu/Bahasa/Format Tanggal/URL Aplikasi dihapus; card SEO, Open Graph, Kontak & Footer tetap. Rute per bagian: `PUT /appearance/{identity,seo,og,contact}`.
- **Penyimpanan**: urutan kolom Driver aktif | Endpoint · Bucket | **Path (baru)** · Access Key ID | Secret · Region | URL Publik. `s3_path` dipakai `FileStorage` sebagai prefix folder di S3.
- **Kolom `office` dihapus di semua tempat** termasuk kolom DB (migrasi `2026_08_15_030000_drop_office_from_users_table`), tabel & dialog Pengguna, seeder/factory, share Inertia.
- Uji iterasi 8: backend 26/26 lolos (validasi telepon huruf ditolak, username huruf besar ditolak, path/endpoint/URL, dll). Iterasi 9: frontend 100% (validasi blur + toast).

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

## Selesai (2026-06-15, semua Ekspor/Impor pindah ke XLSX)
- Lingkungan: pod restart membuat PHP hilang lagi → dipasang ulang (lihat `/app/memory/env_notes.md`). **Composer kini terpasang** di `/usr/local/bin/composer`.
- Paket baru `phpoffice/phpspreadsheet ^5.9`; `app/Support/Csv.php` **DIHAPUS**, diganti `app/Support/Excel.php`:
  `download($filename, $headers, $rows, $sheetTitle)` (header tebal + fill, freeze `A2`, auto-filter, auto width, nilai teks ditulis eksplisit agar `081...` tidak jadi angka), `rows($path)` (baca .xlsx/.xls, baris kosong dibuang), `filename($prefix)` → `.xlsx`.
- Ekspor `.xlsx`: `/users/export`, `/permissions/export`, `/audit-trail/export` (tetap mengikuti filter aktif). Audit trail mencatat "(Excel)".
- Impor `.xlsx`/`.xls`: `/users/import` (kolom sesuai template) & `/roles/import` (nama peranan di kolom pertama). `mimes:xlsx,xls` di `ImportUserRequest` (maks 2 MB) & `ImportRoleRequest` (maks 1 MB) — CSV kini ditolak.
- **Template contoh** baru: `GET /users/import/template`, `GET /roles/import/template` (berisi 2 baris teladan). Dialog impor punya tombol **Template** (kiri, `mr-auto`) dan deskripsinya tidak lagi menyebut daftar kolom.
- Uji: `tests/Feature/ExcelIoTest.php` — 4 lolos (5 unduhan menghasilkan xlsx terbaca, impor pengguna & peranan dari xlsx, berkas CSV ditolak). Screenshot dialog Impor Pengguna diverifikasi.

## Selesai (2026-06-15, halaman error bertema + fix 419 di iframe preview)
- **Halaman error senada design system**: `resources/js/pages/Error.vue` (header brand + ModeToggle, chip ikon + kode status, judul besar, deskripsi, tombol Kembali Ke Dasbor/Masuk + Muat Ulang, meta Alamat & Kode Referensi, email dukungan). Katalog status: 401, 403, 404, 419, 429, 500, 503.
- Dirender lewat `$exceptions->respond()` di `bootstrap/app.php` (dilewati untuk permintaan JSON; 5xx tetap memakai halaman debug Laravel saat `APP_DEBUG=true`) + `Route::fallback()` di `routes/web.php` agar 404 melewati grup `web` sehingga sesi & prop Inertia (branding, auth) tersedia.
- **Bug**: `AuthenticationException` (belum masuk) sebelumnya tercatat sebagai "Kegagalan sistem" level danger di audit trail → kini diabaikan.
- **Bug 419 di panel Preview (iframe)**: cookie sesi `SameSite=Lax` diblokir pada konteks lintas situs → token CSRF hilang. Fix: `SESSION_SAME_SITE=none` + `SESSION_SECURE_COOKIE=true` di `.env`. Diverifikasi login berhasil dari dalam iframe lintas domain.
- Uji: `tests/Feature/ErrorPageTest.php` 3 lolos (404 & 403 → komponen Inertia `Error`, permintaan JSON tetap JSON) + screenshot terang/gelap.

## Selesai (2026-06-16, sidebar diringkas + modul Penyimpanan dihapus + urutan entitas dapat digeser)
- **Menu Profil dihapus** dari sidebar (area Member hanya Dashboard); halaman `/profile` tetap ada & diakses lewat dropdown footer sidebar.
- **Modul Penyimpanan DIHAPUS total**: `StorageController`, `Requests/StorageSetting/`, `pages/Storage.vue`, rute `/storage-settings*`, izin `storage.view|manage` (dihapus lewat seeder), dan setelan `s3_*`/`storage_driver` di tabel `settings`. `App\Support\FileStorage` kini **100% dari .env**: `FILESYSTEM_DISK`, `AWS_*` + tambahan `AWS_ENDPOINT`, `AWS_URL`, `AWS_PATH` (dipakai sebagai `root` disk s3 di `config/filesystems.php`).
- **Urutan menu Administrator**: Perizinan → Peranan → Pengguna → **Penampilan** → Audit Trail.
- **Kartu entitas pada matriks hak akses dapat digeser** (HTML5 drag & drop, tanpa dependensi baru; ikon `GripVertical`, ring saat drop). Urutan disimpan global di `settings.permission_entity_order` lewat `PUT /roles/entity-order` (`SaveEntityOrderRequest`, izin `roles.manage`) dan dipakai `RoleController::matrix()`; entitas baru menyusul di belakang. Diverifikasi: urutan tetap setelah reload.
- **BUG LOGIN diperbaiki**: `LoginRequest::authenticate()` sebelumnya menebak kolom dari format masukan, sehingga **username numerik** (`309011221`) dianggap nomor telepon → selalu gagal. Sekarang pengguna dicari sekaligus pada `email`/`username`/`phone`, lalu `Auth::attempt(['id' => ...])`.
- Kredensial seeder saat ini: username `309011221`, kata sandi `1` (lihat `/app/memory/test_credentials.md`) — `php artisan db:seed` MENIMPA kata sandi.
- Catatan: `tests/Feature/ExampleTest.php` (bawaan Laravel) gagal karena `/` butuh sesi — bukan regresi.

## Selesai (2026-06-16, Laravel Telescope + Laravel Lang)
- **Composer dipindah ke `/app/bin/composer`** (symlink ke `/usr/local/bin`) karena `/usr/local` hilang tiap pod restart.
- **laravel/telescope ^5.22** di `/telescope`: middleware `['web','auth',Authorize::class]`, gate `viewTelescope` hanya untuk email di `TELESCOPE_ALLOWED_EMAILS` (`.env` = zulfadlirizal@gmail.com), `authorization()` di-override agar gate berlaku juga saat `APP_ENV=local`.
  - **Jebakan penting**: Telescope 5 membawa `laravel/sentinel` yang menyisipkan `SentinelMiddleware:telescope` dan mengembalikan **401** ketika `APP_ENV=local` diakses lewat reverse proxy publik (pod preview). Fix: `TelescopeServiceProvider::boot()` mendaftarkan ulang `Route::middlewareGroup('telescope', config('telescope.middleware'))` tanpa Sentinel.
  - Terverifikasi: tamu → 302 /login, email diizinkan → UI Telescope 200, pengguna lain → 403 halaman error bertema.
- **laravel-lang/common ^6.8** + `php artisan lang:add id` → `lang/id/*` & `lang/id.json`; `APP_LOCALE=id` membuat pesan validasi bawaan berbahasa Indonesia. Pesan kustom (`Rules::messages()`, `messages()` tiap Form Request, halaman Login) TIDAK diubah dan tetap menang. Terverifikasi lewat Validator: unique → "Surel sudah ada sebelumnya.", in → "Peran yang dipilih tidak valid.", sedangkan phone.regex tetap pesan kustom.

## Selesai (2026-06-16, dokumentasi diperbarui)
- README dirapikan: blok Fitur "Manajemen Pengguna"/"Perizinan" yang duplikat & rusak diperbaiki; ditambah halaman error bertema, struktur sidebar baru (Profil tidak lagi jadi menu), drag & drop urutan entitas matriks, tabel Telescope di skema DB, rute `PUT /roles/entity-order` + `/telescope` + `Route::fallback`, env penting (`SESSION_SAME_SITE`, `FILESYSTEM_DISK`, `TELESCOPE_*`), perintah uji (`ExcelIoTest`, `ErrorPageTest`), dan 4 entri baru Pemecahan Masalah.
- **Berkas contoh env bernama `env.example` (tanpa titik, sengaja, agar ter-push ke GitHub) — JANGAN dihapus/ubah nama.** Disinkronkan dengan kunci baru: `SESSION_SAME_SITE`, `SESSION_SECURE_COOKIE`, `AWS_ENDPOINT`, `AWS_URL`, `AWS_PATH`, `TELESCOPE_ENABLED`, `TELESCOPE_ALLOWED_EMAILS`. Perintah di README kini `cp env.example .env`.

## Selesai (2026-06-16, Salin Hak Akses saat membuat peranan)
- Dialog **Tambah Peranan** kini punya Combobox opsional **Salin Hak Akses Dari** (`data-testid=role-form-copy-from`) berisi daftar peranan + jumlah izinnya, plus teks bantuan dinamis (`role-form-copy-hint`). Hanya muncul saat membuat (tidak pada Ubah).
- Backend: `StoreRoleRequest` menerima `copy_from` (`nullable|integer|exists:roles,id`); `RoleController::store()` menyalin `syncPermissions()` dari peranan sumber, mencatat konteks audit (`hak_akses_disalin_dari`, `jumlah_izin`), dan flash sukses menyebut jumlah izin + nama sumber.
- Terverifikasi end-to-end: menyalin dari Super Admin → 12 izin tersalin (dikonfirmasi di halaman detail peranan baru). Peranan uji dibersihkan.

## Selesai (2026-06-16, seeder = snapshot data saat ini)
- `database/seeders/DatabaseSeeder.php` ditulis ulang sebagai **cetakan data produksi saat ini**, tanpa data contoh/factory:
  - 12 izin dari `Modules::permissions()`; peranan `Super Admin` (semua izin) & `Guest` (`dashboard.view`, `profile.view`).
  - 1 pengguna: Super Admin / username `jkv` / `studio@jkv.co.id` / `082320099971`, hash kata sandi disimpan **apa adanya** (`$2y$12$7zX...`, kata sandi saat ini `1`) supaya seeding ulang tak mengubah kredensial. `syncRoles(['Super Admin'])`.
  - 24 setelan (branding + SEO + `permission_entity_order`) sebagai konstanta `SETTINGS`.
- Uji: `DB_DATABASE=/tmp/seedtest.sqlite php artisan migrate:fresh --seed --force` → users=1, roles=2, perms=12, settings=24, logs=0, password `1` cocok. DB asli tidak disentuh.
- **PERINGATAN terbuka**: `.env` `TELESCOPE_ALLOWED_EMAILS=zulfadlirizal@gmail.com`, sedangkan email Super Admin kini `studio@jkv.co.id` → `/telescope` akan 403 sampai nilai env diperbarui (menunggu keputusan user).
- **Resolved**: `TELESCOPE_ALLOWED_EMAILS` diubah ke `studio@jkv.co.id` (email Super Admin baru); `/telescope` terverifikasi 200 setelah login `jkv` / `1`. `env.example` & README ikut disesuaikan.

## Selesai (2026-06-16, Dashboard data nyata + Galeri Komponen)
- **`app/Http/Controllers/DashboardController.php` baru** (menggantikan closure + `App\Support\DemoData` yang DIHAPUS). Semua widget kini data nyata: KPI (pengguna aktif/nonaktif, peranan, izin + jumlah entitas, aktivitas 7 hari + level danger/warning, notifikasi belum dibaca), Pengguna Terbaru (kelengkapan profil dari username/email/phone/avatar), Aktivitas Terakhir (activity_logs), Tren 7 hari (pengguna baru vs aktivitas), Aktivitas per Modul, **Sebaran Peranan** (`roleDistribution`, sebelumnya hardcode), Penyimpanan nyata (ukuran berkas disk public, ukuran file SQLite, disk server).
- **`resources/js/components/composite/ComponentGallery.vue` baru** — showcase interaktif SEMUA komponen: tombol (5 varian + icon + status muat), 4 toast, Badge 4 varian, StateChip 6 status, Avatar, Tooltip, Input + state error, PasswordInput, PhoneInput, Textarea, Combobox, DatePicker, Checkbox, Switch, Alert (2 varian), Progress (+/−10), Skeleton, Dialog, ConfirmDeleteDialog, DropdownMenu, Tabel + RowActions, EmptyState (6 varian dipilih via Combobox).
- Dashboard juga: tombol Segarkan/Pengguna Baru/Lihat Semua kini benar-benar berfungsi, EmptyState saat daftar kosong, tinggi kartu daftar `lg:h-[26rem]`.
- Uji: testing agent iterasi 22 → **100% lulus, 0 bug, 0 console error**, termasuk regresi 5 halaman lain, dark mode, dan mobile 390x844 tanpa overflow (`/app/test_reports/iteration_22.json`).

## Selesai (2026-06-16, manajemen notifikasi + Dashboard dirapikan)
- **Lonceng notifikasi**: tombol **Tandai Semua** (label diperjelas, `POST /notifications/read-all` sudah ada) + **penyaring Semua / Belum Dibaca** (`notifications-filter-all` / `notifications-filter-unread`, badge jumlah belum dibaca). `HandleInertiaRequests` kini membagikan `items` DAN `unread_items` (10 terbaru, lazy closure) via helper `mapNotifications()`; teks kosong kontekstual.
- **BUG diperbaiki**: `DashboardController` KPI notifikasi memakai kolom `is_read` yang tidak ada → diganti `whereNull('read_at')` (tabel `notifications` memakai `read_at`).
- **Dashboard dirapikan atas permintaan user** ("hapus saja konten itu", rekomendasi diikuti): kartu **Pengguna Terbaru dihapus** (nyaris kosong dengan 1 pengguna) beserta prop `recentUsers` & method controllernya. Aktivitas Terakhir kini 2 kolom (tombol Segarkan/Pengguna Baru dipindah ke sana, `max-h-80` scroll) dan Penyimpanan 1 kolom, tinggi mengikuti isi (tanpa tinggi terkunci).
- Terverifikasi via screenshot: filter Semua/Belum Dibaca, Tandai Semua → hitungan 0 + "Semua notifikasi sudah dibaca.", KPI notifikasi menampilkan angka benar. Notifikasi uji buatan sudah dihapus.

## Selesai (2026-06-18, deteksi jaringan + halaman pemeliharaan + redesain halaman error)
- **Deteksi jaringan**: `resources/js/composables/useNetworkStatus.js` (event `online`/`offline` + hook Inertia `exception`/`success`) & `components/layout/OfflineBanner.vue` (banner destructive di bawah header + tombol Coba Lagi) dipasang di `AppLayout`. Toast: "Koneksi terputus…" / "Koneksi kembali normal." Terverifikasi memakai `context.set_offline()`.
- **Halaman pemeliharaan** `resources/views/errors/503.blade.php` — mandiri (tanpa Vite/DB/sesi), mode gelap via `prefers-color-scheme`, kisi latar + panel 503 + bilah progres animasi + daftar langkah, auto-refresh sesuai `--retry`. Branding & `retryAfter` (dibaca dari `storage/framework/down`) diinjeksi lewat `View::composer('errors::503')` di `AppServiceProvider`.
  - **Akar masalah "masih default Laravel" saat `php artisan down`**: hook `respond()` melewatkan SEMUA status >= 500 ketika `APP_DEBUG=true`. Sekarang: bypass debug hanya untuk 500, dan **503 dikeluarkan dari daftar Inertia** agar memakai Blade mandiri.
- **Redesain `pages/Error.vue`** (401/403/404/419/429/500): layout 2 kolom, chip status dengan titik ping, judul & deskripsi khusus per status + baris `hint`, tombol aksi, meta (alamat/kode referensi/waktu), panel kode status dengan angka mono besar + arsir diagonal + animasi bertahap, kartu **Pintasan Cepat** (Dashboard/Pengguna/Peranan/Perizinan/Audit Trail) untuk pengguna terautentikasi, footer `HTTP <status>`.
- Uji: `ErrorPageTest` 3/3 lolos; screenshot 404 tamu & login, mode terang/gelap, halaman 503 terang/gelap/mobile (overflow 0). Aplikasi sudah `php artisan up` kembali.

## Selesai (2026-06-18, Menu Builder + konsistensi ukuran kontrol)
- **Modul Menu Sidebar** (`/menus`): tabel `menus` (parent_id, area, label, href, icon, permission, sort, is_active), `App\Models\Menu` (`tree()`, `treeFor()` filter izin + buang grup kosong, MAX_DEPTH=3), `MenuController` (index/store/update/destroy/reorder), 3 FormRequest, izin baru `menus.view|menus.manage` (entitas `menus` di Modules::MAP). Sidebar dirender dari prop Inertia `menu` lewat komponen rekursif `MenuNode.vue`; `navigation.js` kini hanya menyimpan `AREA_META` + breadcrumb. Seeder menambah menu bawaan + contoh **Level 1 → Level 2 → Level 3** di Member Area.
- Builder: geser baris untuk urutan + tombol indent/outdent untuk tingkat, dialog CRUD (label, area, induk, alamat, ikon, izin, aktif). Daftar reaktif terhadap props (perbaikan temuan iterasi 23).
- **BUG USER: ukuran kontrol tidak konsisten** — Combobox memakai `--ctl-h-sm` (28px, text-xs) sedangkan Input `--ctl-h` (32px, text-sm), dan input file mentah lebih tinggi. Fix: Combobox → `--ctl-h`/text-sm/px-3; komponen baru `ui/FileInput.vue` (32px, expose `clear()`) dipakai di dialog impor Pengguna & Peranan; `Button` size `sm` → `--ctl-h`; override `h-[var(--ctl-h-sm)]`/`text-xs` di DataTableCard (search, paginasi) & DatePicker AuditTrail dihapus. Token `--ctl-h-sm` kini tidak dipakai lagi di komponen kontrol.
- Uji: iterasi 23 (Menu Builder, semua alur lulus) & **iterasi 24 (verifikasi ukuran: semua kontrol form & tombol = 32px, 0 bug, regresi filter/pilih berkas/menu reaktif/mobile lulus)**.
- **Catatan akun**: kata sandi akun pemilik (`jkv` / studio@jkv.co.id) sudah diganti sendiri oleh user → tidak diketahui agen. Dibuat akun uji **qatest / qatest123 (Super Admin, qa@example.com)** untuk pengujian; user boleh menghapusnya kapan saja lewat menu Pengguna.

## Selesai (2026-06-18, bug "login harus langsung ke dashboard")
- Akar masalah: (1) `AuthenticatedSessionController@store` memakai `redirect()->intended()` → bila tamu sebelumnya membuka halaman terproteksi (/menus, /users, /telescope), setelah masuk ia dibawa ke sana; (2) `AppSidebar.onMounted` memulihkan area dari `localStorage` sehingga sidebar bisa menampilkan area Administrator padahal halaman dashboard ada di Member Area.
- Fix: `session()->forget('url.intended')` + `redirect()->route('dashboard')`; `onMounted` memakai `areaIdOf(pathname)` (route menang atas localStorage; `watch(pathname)` tetap menyesuaikan area saat navigasi).
- Uji: testing agent iterasi 25 → **7/7 skenario lulus, 0 bug** (sesi bersih, skenario intended URL untuk 3 path, skenario localStorage 'admin', toast sambutan, regresi navigasi/refresh /users, logout).
- Catatan lingkungan: PHP hilang lagi setelah pod restart → dibuat skrip pemulihan **`/app/memory/restore_php.sh`** (pasang PHP 8.3 + symlink composer dari `/app/bin/composer` + restart supervisor).
