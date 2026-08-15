# CODEX — AdminKit Starter Kit

Starter kit panel admin **compact UI** yang siap dikembangkan: Laravel 12 + Vue 3 + Inertia.js + TailwindCSS 3 di atas SQLite, lengkap dengan autentikasi, hak akses berbasis peranan, audit trail, pengaturan penampilan, dan object storage (S3) yang dikonfigurasi lewat `.env`.

> Design system mengikuti **FlowDesk** (compact, monokrom) dengan komponen porting **shadcn/ui** dan dukungan **dark mode**.

---

## Daftar Isi

- [Tumpukan Teknologi](#tumpukan-teknologi)
- [Fitur](#fitur)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Perintah Harian](#perintah-harian)
- [Struktur Proyek](#struktur-proyek)
- [Skema Basis Data](#skema-basis-data)
- [Ekspor & Impor Excel](#ekspor--impor-excel)
- [Standar Validasi (WAJIB)](#standar-validasi-wajib)
- [Hak Akses & Peranan](#hak-akses--peranan)
- [Audit Trail](#audit-trail)
- [Notifikasi](#notifikasi)
- [Aksi Massal (Bulk Action)](#aksi-massal-bulk-action)
- [Pengaturan Penampilan (Branding)](#pengaturan-penampilan-branding)
- [Object Storage (S3)](#object-storage-s3)
- [Design System & Konvensi UI](#design-system--konvensi-ui)
- [Tabel Server-side](#tabel-server-side)
- [Rute](#rute)
- [Telescope (Debug)](#telescope-debug)
- [Bahasa & Pesan Validasi](#bahasa--pesan-validasi)
- [Pengujian](#pengujian)
- [Deployment](#deployment)
- [Pemecahan Masalah](#pemecahan-masalah)

---

## Tumpukan Teknologi

| Lapisan | Teknologi |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3 (Composition API), Inertia.js 2, Vite 6 |
| Styling | TailwindCSS 3, komponen porting shadcn/ui, `lucide-vue-next` |
| Basis Data | SQLite (mudah diganti MySQL/PostgreSQL) |
| Hak Akses | `spatie/laravel-permission` |
| Penyimpanan | Disk `public` (lokal) atau S3 via `league/flysystem-aws-s3-v3`, dipilih oleh `FILESYSTEM_DISK` |
| Kualitas Kode | Laravel Pint, Prettier |

---

## Fitur

**Autentikasi & Akun**
- Masuk memakai **email, nama pengguna, atau nomor HP** dengan satu kolom kredensial.
- Pembatasan percobaan masuk (rate limit 5 percobaan) + pencatatan percobaan gagal.
- Halaman profil: ubah data diri, unggah/hapus foto profil, ganti kata sandi.

**Manajemen Pengguna**
- Tabel server-side: pencarian, sortir, paginasi, filter **Peranan** (dinamis) dan **Status**.
- Dialog tambah/ubah dengan validasi cepat; hanya **Nama, Peranan, Kata Sandi** yang wajib.
- **Impor Excel** (`.xlsx` sesuai template yang dapat diunduh; baris judul diabaikan, baris tidak valid dilewati, kata sandi kosong diisi acak) dan **Ekspor Excel** mengikuti filter aktif.
- `username`, `email`, `phone` opsional namun **unik**; nomor HP hanya menerima angka (boleh `+`).

**Perizinan**
- Halaman `/permissions` untuk mengelola permission Spatie: tabel server-side (pencarian, sortir, filter **Entitas** dinamis, paginasi), tambah/ubah/hapus, dan hapus massal.
- Nama izin wajib berformat `entitas.aksi` huruf kecil (mis. `projects.view`, `projects.delete_any`).
- **Izin inti** bawaan modul (`Modules::permissions()`) terkunci: ikon kunci, tanpa menu aksi, dan ditolak 403 dari server bila dipaksa diubah/dihapus.
- **Generator izin standar**: masukkan entitas lalu pilih aksi (`view`, `view_any`, `create`, `update`, `delete`, `delete_any`) — izin yang sudah ada dilewati.

**Peranan** (dinamis) dan **Status**.
- Dialog tambah/ubah dengan validasi cepat; hanya **Nama, Peranan, Kata Sandi** yang wajib.
- **Impor Excel** (`.xlsx` sesuai template yang dapat diunduh; baris judul diabaikan, baris tidak valid dilewati, kata sandi kosong diisi acak) dan **Ekspor Excel** mengikuti filter aktif.
- `username`, `email`, `phone` opsional namun **unik**; nomor HP hanya menerima angka (boleh `+`).

**Perizinan**
- Halaman `/permissions` untuk mengelola permission Spatie: tabel server-side (pencarian, sortir, filter **Entitas** dinamis, paginasi), tambah/ubah/hapus, dan hapus massal.
- Nama izin wajib berformat `entitas.aksi` huruf kecil (mis. `projects.view`, `projects.delete_any`).
- **Izin inti** bawaan modul (`Modules::permissions()`) terkunci: ikon kunci, tanpa menu aksi, dan ditolak 403 dari server bila dipaksa diubah/dihapus.
- **Generator izin standar**: masukkan entitas lalu pilih aksi (`view`, `view_any`, `create`, `update`, `delete`, `delete_any`) — izin yang sudah ada dilewati.

**Peranan**
- CRUD peranan, peranan `Super Admin` terkunci dari perubahan/penghapusan.
- **Impor peranan dari Excel** (nama peranan pada kolom pertama, baris judul diabaikan, duplikat dilewati).
- **Salin Hak Akses**: pada dialog *Tambah Peranan* ada pilihan **Salin Hak Akses Dari** (opsional) — seluruh izin peranan sumber langsung disalin ke peranan baru; jumlah izin ditampilkan di daftar pilihan dan pada teks bantuan.
- **Matriks hak akses** di halaman detail peranan: izin dikelompokkan per entitas, toggle "pilih semua" per entitas dan global, pencarian izin, penghitung izin terpilih (Super Admin bersifat read-only).

**Audit Trail**
- Mencatat siapa mengubah apa, **diff nilai sebelum → sesudah**, konteks permintaan, dan kegagalan sistem.
- Halaman detail khusus pengembang termasuk **Payload Mentah (JSON)**.
- Hapus jejak audit berdasarkan rentang tanggal.

**Ekspor & Impor**
- Ekspor Excel **mengikuti filter aktif** di Pengguna (`/users/export`), Perizinan (`/permissions/export`), dan Audit Trail (`/audit-trail/export`) — `.xlsx` via `App\Support\Excel` (PhpSpreadsheet).
- Impor Excel untuk Pengguna (`POST /users/import`) dan Peranan (`POST /roles/import`), lengkap dengan template contoh (`/users/import/template`, `/roles/import/template`).

**Notifikasi**
- Notifikasi **per pengguna** (tabel `notifications`, satu baris = satu penerima) — bukan siaran ke semua orang.
- Bertarget izin: hanya pengguna aktif yang memiliki izin modul terkait yang menerimanya, dan **pelaku aksi tidak menerima notifikasi atas aksinya sendiri**.
- Lonceng di header menampilkan jumlah belum dibaca, tombol **Tandai** (tandai semua dibaca), dan klik item menandai dibaca lalu membuka halaman terkait.

**Aksi Massal**
- Checkbox pada tabel Pengguna & Peranan (pilih baris / pilih semua baris pada halaman aktif).
- Pengguna: **Aktifkan**, **Nonaktifkan**, **Hapus** (akun sendiri otomatis dilewati).
- Peranan: **Hapus** (Super Admin dan peranan yang masih dipakai otomatis dilewati).

**Pengaturan**
- **Penampilan**: identitas aplikasi (nama, tagline, inisial brand), logo terang/gelap, favicon, SEO & metadata (termasuk Open Graph), kontak & footer.

**Dashboard**
- Widget **data nyata** dari basis data: KPI (pengguna aktif/nonaktif, peranan, izin + jumlah entitas, aktivitas 7 hari + yang perlu ditinjau, notifikasi belum dibaca), Pengguna Terbaru (dengan kelengkapan profil), Aktivitas Terakhir, Tren 7 Hari (pengguna baru vs aktivitas), Aktivitas per Modul, Sebaran Peranan, dan Penyimpanan nyata (berkas unggahan, ukuran basis data, disk server).
- **Galeri Komponen** (`components/composite/ComponentGallery.vue`): showcase interaktif seluruh komponen — tombol & status muat, toast 4 varian, lencana + StateChip, avatar, tooltip, isian formulir (Input, error state, PasswordInput, PhoneInput, Textarea), Combobox, DatePicker, Checkbox, Switch, Alert, Progress, Skeleton, Dialog, ConfirmDeleteDialog, DropdownMenu, Tabel + RowActions, dan EmptyState (6 varian dapat dipilih).

**UI**
- Dark mode, sidebar dapat di-collapse (mode ikon), breadcrumb otomatis, toast, dialog, combobox dengan pencarian, date picker.
- Sidebar dua area: **Member Area** (Dashboard) dan **Administrator** (Perizinan → Peranan → Pengguna → Penampilan → Audit Trail). Profil diakses lewat dropdown akun di footer sidebar.
- **Halaman error bertema design system** (`pages/Error.vue`) untuk 401/403/404/419/429/500/503 — bukan tampilan bawaan Laravel.
- Responsif: kolom tabel sekunder otomatis disembunyikan pada layar kecil.

---

## Persyaratan

- PHP **8.2+** dengan ekstensi: `sqlite3`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `gd`, `intl`
- Composer 2
- Node.js 18+ dan **Yarn**

---

## Instalasi

```bash
git clone <url-repo> adminkit && cd adminkit

composer install
yarn install

cp env.example .env           # berkas contoh bernama env.example (tanpa titik) agar ikut ter-push ke GitHub
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed    # membuat peranan, izin, akun Super Admin, dan setelan
php artisan storage:link      # agar berkas unggahan lokal dapat diakses

yarn build                    # atau: yarn dev (mode pengembangan)
php artisan serve
```

Variabel `.env` yang relevan:

```env
APP_LOCALE=id                 # tanggal, waktu relatif, dan pesan validasi (Laravel Lang)
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=sqlite
DB_DATABASE=/abs/path/database/database.sqlite

SESSION_SAME_SITE=none        # WAJIB bila aplikasi dimuat di dalam iframe (mis. panel preview)
SESSION_SECURE_COOKIE=true    # pasangan dari SameSite=none — hanya untuk HTTPS

FILESYSTEM_DISK=local         # local atau s3 (lihat Object Storage)
TELESCOPE_ENABLED=true
TELESCOPE_ALLOWED_EMAILS=email@anda.com
```

`database/seeders/DatabaseSeeder.php` adalah **cetakan data awal proyek** — cerminan data yang sedang dipakai: 1 pengguna Super Admin, peranan `Super Admin` + `Guest`, 12 izin dari `Modules::MAP`, dan seluruh setelan branding/SEO. Tidak ada data contoh/factory yang dibuat.

- Idempoten: `php artisan db:seed` aman diulang (`updateOrCreate`/`findOrCreate`), tidak menghapus data lain.
- Kata sandi Super Admin disimpan sebagai **hash apa adanya** sehingga seeding ulang tidak mengubah kredensial yang sedang dipakai. **Ganti kata sandi setelah instalasi** dan perbarui hash di seeder bila ingin cetakan barunya ikut berubah.
- Perubahan setelan (branding/SEO/urutan entitas) yang ingin dijadikan bawaan proyek cukup disalin ke konstanta `SETTINGS` pada seeder.

---

## Perintah Harian

```bash
yarn dev                 # Vite dev server (HMR)
yarn build               # kompilasi aset produksi — WAJIB setelah mengubah .vue/.css bila tidak memakai yarn dev
php artisan migrate      # migrasi
php artisan db:seed      # seeding ulang (idempoten)
php artisan cache:clear  # WAJIB setelah mengubah tabel settings langsung dari DB (branding di-cache)
./vendor/bin/pint        # format kode PHP
```

---

## Struktur Proyek

```text
app/
├── Enums/RoleName.php                  # enum nama peranan
├── Http/
│   ├── Controllers/                    # Auth, DashboardController, User, Permission, Role, Notification, Profile, Appearance, ActivityLog
│   ├── Middleware/HandleInertiaRequests.php   # share auth, branding, flash
│   └── Requests/                       # SATU Form Request per form (lihat Standar Validasi)
├── Models/                             # User, Role & Permission (Spatie), ActivityLog, Notification, Setting
├── Providers/
│   ├── AppServiceProvider.php          # branding untuk blade root + locale Carbon
│   └── TelescopeServiceProvider.php    # gate & middleware Telescope
└── Support/
    ├── Branding.php                    # pembacaan setelan branding (+cache)
    ├── Excel.php                       # unduhan & pembacaan berkas .xlsx (PhpSpreadsheet)
    ├── FileStorage.php                 # satu pintu unggahan (local/s3, prefix disk)
    ├── Modules.php                     # daftar modul & izin inti
    ├── Notify.php                      # notifikasi bertarget izin
    ├── Rules.php                       # SATU sumber aturan validasi per tipe kolom
    └── TableQuery.php                  # helper query tabel server-side

resources/
├── css/app.css                         # token FlowDesk, dark mode, densitas tabel
├── js/
│   ├── components/
│   │   ├── composite/                  # DataTableCard, RowActions, StateChip, BrandMark, AssetUploader, dll
│   │   ├── layout/                     # AppLayout, AppSidebar, AuthLayout
│   │   └── ui/                         # porting shadcn/ui (Button, Card, Table, Dialog, Combobox, DatePicker, ...)
│   ├── composables/                    # useServerTable, useLiveValidation, useTheme, useFlashToast, useToast
│   ├── config/navigation.js            # area, menu, breadcrumb (ROUTE_TRAILS)
│   ├── constants/labels.js             # label aksi (Title Case)
│   ├── lib/validators.js               # cermin Rules.php untuk validasi cepat UI
│   └── pages/                          # Dashboard, Users, Permissions, Roles, RoleDetail, AuditTrail, AuditDetail, Appearance, Profile, Error, auth/Login
└── views/app.blade.php                 # root blade (judul, favicon, meta SEO/OG)

lang/id/, lang/id.json                  # terjemahan Laravel Lang (pesan validasi bawaan)
routes/web.php
database/{migrations,seeders,factories}
```

---

## Skema Basis Data

| Tabel | Isi penting |
| --- | --- |
| `users` | `name` (wajib), `username`/`email`/`phone` (opsional & unik), `password`, `avatar`, `is_active`, `last_login_at` |
| `roles`, `permissions`, `model_has_roles`, `role_has_permissions` | standar `spatie/laravel-permission`; nama izin memakai pola `entitas.aksi` |
| `activity_logs` | `actor_name`, `action`, `module`, `level`, `subject_type/id`, `changes` (JSON diff), `context` (JSON), `ip`, `method`, `url`, `status_code`, `user_agent` |
| `notifications` | satu baris per penerima: `user_id`, `title`, `body`, `module`, `level`, `url`, `actor_id`, `read_at` |
| `settings` | `key` (primary), `value` — branding, SEO, dan `permission_entity_order` (urutan kartu entitas matriks) |
| `telescope_entries`, `telescope_entries_tags`, `telescope_monitoring` | penyimpanan Laravel Telescope |

---

## Ekspor & Impor Excel

**Ekspor** memakai `App\Support\Excel::download()` (PhpSpreadsheet, berkas `.xlsx` dengan header tebal, baris pertama dibekukan, auto-filter, lebar kolom otomatis) dan **selalu menghormati filter aktif**:

| Halaman | Rute | Kolom |
| --- | --- | --- |
| Pengguna | `GET /users/export?search=&status=&role=` | Nama Lengkap, Nama Pengguna, Alamat Email, Nomor HP, Peranan, Status, Terakhir Login |
| Perizinan | `GET /permissions/export?search=&entity=&sort=&dir=` | Nama Izin, Entitas, Aksi, Guard, Jumlah Peranan |
| Audit Trail | `GET /audit-trail/export?search=&date_from=&date_to=&sort=&dir=` | Waktu, Pelaku, Aksi, Modul, Level, Alamat IP, Metode, Kode Status, URL |

Di frontend, URL dibangun dari state `useServerTable` lalu dipakai pada `<Button as="a" :href="exportUrl">`.

**Impor** memvalidasi tiap baris memakai `App\Support\Rules` — baris tidak valid dilewati dan jumlahnya dilaporkan:

| Target | Rute | Format berkas |
| --- | --- | --- |
| Pengguna | `POST /users/import` | `.xlsx`/`.xls` sesuai template `GET /users/import/template` (baris judul diabaikan; kata sandi kosong → acak 12 karakter) |
| Peranan | `POST /roles/import` | `.xlsx`/`.xls` sesuai template `GET /roles/import/template` (nama peranan pada kolom pertama, duplikat dilewati) |

Tombol **Template** pada dialog impor mengunduh berkas contoh yang sudah berisi baris data teladan.

---

## Standar Validasi (WAJIB)

Aturan ini berlaku untuk **setiap** form baru — jangan berimprovisasi.

**1. Backend — satu Form Request per form.** Simpan di `app/Http/Requests/<Domain>/<Aksi><Entitas>Request.php`. Controller hanya menerima Form Request; **tidak boleh** `$request->validate()` inline.

**2. Aturan per tipe kolom dipusatkan di `app/Support/Rules.php`.**

| Helper | Aturan |
| --- | --- |
| `Rules::personName()` | wajib/opsional, 3–100 karakter, huruf/spasi/titik/apostrof/tanda hubung |
| `Rules::username($ignoreId)` | `alpha_dash`, huruf kecil, 3–50, unik |
| `Rules::email($ignoreId)` | `email:rfc`, maks 150, unik |
| `Rules::phone($ignoreId)` | `^\+?[0-9]{9,15}$`, unik |
| `Rules::password()` | minimal 8 karakter (`Password::min(8)`) |
| `Rules::text($max)` / `url()` / `slug()` / `path()` / `date()` | teks, URL http/https, slug teknis, path folder, `Y-m-d` |

Contoh:

```php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name' => Rules::personName(),
            'username' => Rules::username($id),
            'email' => Rules::email($id),
            'phone' => Rules::phone($id),
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'password' => Rules::password($id === null),
        ];
    }

    public function attributes(): array { return ['name' => 'nama', /* ... */]; }

    public function messages(): array { return Rules::messages(); }
}
```

**3. Frontend — validasi cepat wajib.** `resources/js/lib/validators.js` adalah **cermin** aturan backend; gunakan bersama `useLiveValidation`:

```js
const form = useForm({ name: '', phone: '' });

const check = useLiveValidation(form, {
    name: all(required('nama'), min(3, 'Nama'), personName('Nama')),
    phone: phone('Nomor HP'),
});

const submit = () => check.submit(() => form.post('/users'));
```

- Pesan error ditulis ke `form.errors` sehingga tampilannya identik dengan error server.
- Validasi dijalankan saat `@blur` per kolom dan sebelum submit.
- Kolom bertipe khusus memakai komponen bertipe: **`PhoneInput`** (menolak non-digit, boleh satu `+`, maks 15 digit), `PasswordInput`, `DatePicker`, `Combobox`.
- Selalu pasang `maxlength` sesuai batas backend dan `novalidate` pada `<form>`.

**4. Nilai kosong tetap kosong.** Untuk setelan (`settings`), `Setting::putMany()` menyimpan `''` bila pengguna mengosongkan kolom; `Branding` hanya memakai nilai default untuk kunci yang **belum pernah** diatur. Jangan mengembalikan nilai lama/ default saat pengguna sengaja mengosongkan kolom.

---

## Hak Akses & Peranan

- Daftar modul dan izin ada di `app/Support/Modules.php` (`<modul>.view`, `<modul>.manage`).
- Rute dilindungi middleware `permission:` — contoh: `Route::middleware('permission:users.manage')`.
- Menu sidebar otomatis menyembunyikan item yang izinnya tidak dimiliki (`resources/js/config/navigation.js` + izin dari share Inertia).
- Peranan `Super Admin` (lihat `App\Enums\RoleName`) terkunci: tidak dapat diubah atau dihapus.

### Matriks hak akses per peranan

- Halaman detail peranan (`/roles/{id}`) menampilkan seluruh izin yang dikelompokkan per **entitas** (bagian sebelum titik) dengan checkbox per aksi, toggle "pilih semua" per entitas, toggle global, pencarian izin, dan penghitung izin terpilih.
- Disimpan lewat `PUT /roles/{role}/permissions` → `syncPermissions()`; peranan `Super Admin` bersifat **read-only** (kontrol disabled dan server menolak 403).
- Perubahan dicatat di audit trail sebagai diff daftar izin lama → baru dan memicu notifikasi bertarget `roles.view`.
- Saat membuat peranan, field opsional `copy_from` (id peranan sumber, divalidasi `Rule::exists`) menyalin seluruh izin peranan tersebut; peranan sumber dan jumlah izin dicatat pada konteks audit trail.
- **Kartu entitas dapat digeser** (drag & drop HTML5, ikon `GripVertical`) untuk menyusun urutan tampilnya. Urutan bersifat **global** (berlaku untuk semua peranan), tersimpan otomatis ke `settings.permission_entity_order` lewat `PUT /roles/entity-order` (`SaveEntityOrderRequest`, izin `roles.manage`), dan dipakai `RoleController::matrix()`; entitas baru menyusul di belakang. Drag dinonaktifkan selama kolom pencarian izin terisi.

### Generator izin standar

Halaman Perizinan menyediakan generator: masukkan entitas (mis. `projects`) lalu pilih aksi `view`, `view_any`, `create`, `update`, `delete`, `delete_any` (`GeneratePermissionRequest::ABILITIES`). Izin dibuat dengan pola `entitas.aksi`; yang sudah ada dilewati.

Menambah izin ad-hoc dapat dilakukan lewat halaman **Perizinan** tanpa menyentuh kode; izin yang dipakai kode aplikasi tetap didaftarkan di `Modules::MAP` agar terkunci dan ikut di-seed.

Menambah modul baru:
1. Tambahkan entri pada `Modules::MAP` (label + izin).
2. `php artisan db:seed` untuk membuat izin baru.
3. Tambahkan rute + middleware `permission:`.
4. Tambahkan menu di `navigation.js` beserta `ROUTE_TRAILS` untuk breadcrumb.

---

## Audit Trail

Rute `/audit-trail` (daftar) dan `/audit-trail/{id}` (detail untuk pengembang).

Mencatat satu jejak:

```php
ActivityLog::record(
    action: "Memperbarui pengguna {$user->name}",
    module: 'Pengguna',
    level: 'info',              // info | success | warning | danger
    subject: $user,
    changes: ActivityLog::diffOf($user, $before),
    context: ['catatan' => 'opsional'],
    statusCode: null,
);
```

Aturan penting:

- **`$before` wajib diambil SEBELUM `save()`**: `$before = $model->getOriginal();` — setelah `save()` Eloquent sudah menyinkronkan nilai aslinya (pernah menjadi bug: `old == new`).
- `ActivityLog::snapshotOf($model)` untuk data baru, `snapshotOf($model, deleted: true)` untuk penghapusan.
- `Setting::putMany()` mengembalikan diff setelan, langsung dipakai sebagai `changes`.
- Kolom rahasia pada `ActivityLog::MASKED` (`password`, `s3_secret`, `s3_key`, dll) otomatis disamarkan `••••••`.
- Konteks permintaan (IP, metode, URL, user agent) diisi otomatis.

Pencatatan otomatis:

| Peristiwa | Level | Status |
| --- | --- | --- |
| Percobaan masuk gagal | `danger` | 422 |
| Akun terkunci (rate limit) | `warning` | 429 |
| Akses ditolak | `danger` | 403 |
| Kegagalan sistem tak tertangani | `danger` | 500 |

Implementasinya di `bootstrap/app.php` memakai **`$exceptions->render()`** (bukan `report()`, karena Laravel mengabaikan turunan `HttpException` saat melapor). Kesalahan validasi (422) dan 404 **sengaja tidak dicatat** agar log tidak bising.

---

## Notifikasi

Kirim notifikasi bertarget dari controller:

```php
Notify::toPermission(
    permission: 'users.view',      // hanya pemegang izin ini yang menerima
    title: 'Pengguna baru terdaftar',
    module: 'Pengguna',
    body: "{$user->name} · peranan {$role}",
    url: '/users',                 // tujuan saat notifikasi diklik
    level: 'success',              // info | success | warning | danger
);

Notify::toUser($user, 'Kata sandi Anda diubah', 'Keamanan');
```

Aturan:
- `Notify::toPermission()` hanya mengirim ke pengguna **aktif** yang lulus `$user->can($permission)` dan **tidak** ke pelaku aksi.
- Daftar & jumlah belum dibaca dibagikan lewat `HandleInertiaRequests` (`notifications.items`, `notifications.unread`) dan **selalu diambil dari `Auth::user()`** sehingga tidak mungkin bocor ke pengguna lain.
- Menandai dibaca melalui `POST /notifications/{id}/read` (dibatasi kepemilikan, selain pemilik → 403) dan `POST /notifications/read-all`.

## Aksi Massal (Bulk Action)

`DataTableCard` menerima prop `selectable` + `selected` dan memancarkan `update:selected`; gunakan slot `#bulk-actions` untuk menaruh tombol:

```vue
<DataTableCard selectable :selected="selected" @update:selected="selected = $event">
    <template #bulk-actions>
        <Button size="sm" variant="destructive" @click="bulkConfirm = true">Hapus</Button>
    </template>
</DataTableCard>
```

Backend memakai Form Request (`BulkUserRequest`, `BulkRoleRequest`) dengan validasi `ids.*` `exists`, mencatat audit trail beserta jumlah baris yang dilewati, dan mengirim notifikasi bertarget.

## Pengaturan Penampilan (Branding)

- Nilai disimpan pada tabel `settings` dan dibaca lewat `App\Support\Branding` (`raw()` untuk form, `values()` untuk tampilan) — hasilnya **di-cache**, jadi jalankan `php artisan cache:clear` bila mengubah langsung dari DB.
- Kunci bertipe aset: `logo_light`, `logo_dark`, `favicon`, `og_image` (lihat `Branding::ASSETS`).
- Urutan tampil merek (`BrandMark.vue`): **logo → inisial brand → ikon**. Bila berkas logo gagal dimuat (mis. driver penyimpanan berganti), komponen otomatis jatuh ke inisial/ikon sehingga tidak pernah tampil "gambar rusak".
- `resources/views/app.blade.php` memakai nilai ini untuk `<title>`, favicon, meta description/keywords, canonical, `noindex`, dan Open Graph.

---

## Object Storage (S3)

Tidak ada halaman pengaturan penyimpanan — **semua kredensial diambil dari `.env`**:

```
FILESYSTEM_DISK=s3          # local (default) atau s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_ENDPOINT=               # untuk S3-compatible (mis. https://nos.wjv-1.neo.id)
AWS_URL=                    # URL publik bila berbeda dari endpoint/bucket
AWS_PATH=                   # prefix folder opsional, mis. adminkit
AWS_USE_PATH_STYLE_ENDPOINT=false
```

- Semua unggahan melewati `App\Support\FileStorage` (`store`, `delete`, `url`).
- Nilai path yang disimpan **berawalan disk**, mis. `local:branding/x.png` atau `s3:branding/x.png`, sehingga berkas lama tetap dapat diakses walau `FILESYSTEM_DISK` diganti.
- Setelah mengubah `.env`, jalankan `php artisan config:clear`.

---

## Design System & Konvensi UI

- Token warna, densitas, dan tipografi ada di `resources/css/app.css` (`--ctl-h`, `--field-gap`, `--item-gap`, `.form-dense`, `.tbl-density`).
- **Badge** memakai referensi asli shadcn/ui (`default`, `secondary`, `destructive`, `outline`) dengan padding compact — tanpa palet warna kustom.
- **Label dan judul memakai Title Case** (mis. `Nama Pengguna`, `Kata Sandi`, `Tambah Pengguna`), bukan Sentence case.
- Jangan menambahkan `CardDescription` di bawah judul kartu.
- Header dialog (`ui/Dialog.vue`): judul dan tombol tutup sejajar vertikal (`items-center`, judul `leading-6`, tombol tutup kotak `size-7`). Judul dialog memakai Title Case dan diakhiri `?` untuk konfirmasi.
- Ikon memakai `lucide-vue-next`; jangan memakai emoji.
- **Setiap elemen interaktif dan informasi penting wajib punya `data-testid`** dengan format kebab-case, mis. `user-form-save`, `users-filter-role`.
- Sidebar mendukung mode ikon (`collapsible="icon"`): elemen non-ikon disembunyikan dengan `group-data-[collapsible=icon]:hidden`, dan ikon utama memakai `shrink-0`.

---

## Tabel Server-side

Backend memakai `App\Support\TableQuery` (search, sort, direction, filter, paginasi):

```php
$search = TableQuery::search($request);
$sort = TableQuery::sort($request, self::SORTABLE, 'name');
$dir = TableQuery::direction($request);
```

Frontend memakai `useServerTable` + `DataTableCard`:

```js
const { query, loading, reload, onSearch, onSort, onPage, onPerPage, onFilter, sortState } = useServerTable({
    url: '/users',
    only: ['users', 'filters'],
    initial: { search: '', sort: 'name', dir: 'asc', status: 'all', role: 'all', page: 1, per_page: 10 },
});
```

Properti kolom `DataTableCard`: `{ key, label, align?, width?, sortable?, sortKey?, hideBelow? }`.
`hideBelow: 'sm' | 'md' | 'lg' | 'xl'` menyembunyikan kolom sekunder pada layar kecil (dipakai di Pengguna & Audit Trail agar tampilan mobile tetap rapi).
Nilai `'all'` dipakai sebagai sentinel filter "semua" karena `reka-ui` melarang nilai kosong pada item.

---

## Rute

| Metode | URI | Keterangan |
| --- | --- | --- |
| GET/POST | `/login`, `/logout` | Autentikasi |
| GET | `/` | Dashboard |
| GET | `/profile` | Profil pengguna |
| PUT | `/profile`, `/profile/password` | Perbarui profil & kata sandi |
| POST/DELETE | `/profile/avatar` | Unggah/hapus foto profil |
| GET | `/users` | Daftar pengguna (search, sort, filter peranan & status) |
| POST/PUT/DELETE | `/users`, `/users/{user}` | CRUD pengguna |
| GET | `/permissions` | Daftar izin (Perizinan) |
| POST/PUT/DELETE | `/permissions`, `/permissions/{permission}` | CRUD izin |
| POST | `/permissions/bulk-destroy` | Hapus massal izin |
| POST | `/permissions/generate` | Generator izin standar per entitas |
| GET | `/permissions/export`, `/users/export`, `/audit-trail/export` | Unduh Excel (.xlsx) sesuai filter aktif |
| POST | `/users/import` | Impor pengguna dari Excel |
| GET | `/users/import/template` | Unduh template impor pengguna |
| PUT | `/roles/{role}/permissions` | Simpan matriks hak akses peranan |
| PUT | `/roles/entity-order` | Simpan urutan kartu entitas pada matriks |
| GET | `/roles`, `/roles/{role}` | Daftar & detail peranan |
| POST/PUT/DELETE | `/roles`, `/roles/{role}` | CRUD peranan |
| POST | `/roles/import` | Impor peranan dari Excel |
| GET | `/roles/import/template` | Unduh template impor peranan |
| POST | `/users/bulk` | Aksi massal pengguna (`delete`/`activate`/`deactivate`) |
| POST | `/roles/bulk-destroy` | Hapus massal peranan |
| POST | `/notifications/read-all`, `/notifications/{notification}/read` | Tandai notifikasi dibaca |
| GET | `/audit-trail`, `/audit-trail/{log}` | Audit trail & detail |
| DELETE | `/audit-trail` | Hapus jejak audit pada rentang tanggal |
| GET | `/appearance` | Pengaturan penampilan |
| PUT | `/appearance/{identity\|seo\|contact}` | Simpan per bagian |
| POST/DELETE | `/appearance/asset/{key}` | Unggah/hapus aset merek |
| GET | `/telescope` | Laravel Telescope (login + email diizinkan) |
| * | selain di atas | `Route::fallback()` → halaman error 404 bertema |

---

## Telescope (Debug)

`laravel/telescope` terpasang di `/telescope` dengan **dua lapis** perlindungan:

1. Middleware `['web', 'auth', Authorize::class]` (`config/telescope.php`) → tamu dialihkan ke `/login`.
2. Gate `viewTelescope` hanya meloloskan email pada `TELESCOPE_ALLOWED_EMAILS` (dipisah koma di `.env`); pengguna lain menerima 403 (halaman error bertema).

```
TELESCOPE_ENABLED=true
TELESCOPE_ALLOWED_EMAILS=studio@jkv.co.id
```

Catatan penting: `App\Providers\TelescopeServiceProvider::boot()` mendaftarkan ulang grup middleware `telescope` **tanpa** `Laravel\Sentinel\Http\Middleware\SentinelMiddleware`. Sentinel memblokir `/telescope` dengan 401 ketika `APP_ENV=local` diakses lewat reverse proxy publik (kasus pod preview), padahal otorisasi sudah ditegakkan oleh sesi login + gate email. `authorization()` juga di-override agar gate berlaku di **semua** environment (bawaan Telescope melewati pemeriksaan saat `local`).

---

## Bahasa & Pesan Validasi

- `laravel-lang/common` terpasang dan berkas bahasa Indonesia ada di `lang/id/` + `lang/id.json` (`php artisan lang:add id`, perbarui dengan `php artisan lang:update`).
- `APP_LOCALE=id` → seluruh pesan validasi bawaan Laravel otomatis berbahasa Indonesia (`required`, `email`, `unique`, `in`, `max`, dll).
- **Pesan kustom tetap menang**: `App\Support\Rules::messages()` dan `messages()` pada setiap Form Request (mis. halaman Login, telepon, username) tidak berubah — Laravel Lang hanya mengisi aturan yang belum punya pesan kustom.

---

## Pengujian

```bash
php artisan test                        # seluruh suite
php artisan test --filter=ExcelIoTest   # ekspor/impor .xlsx & penolakan berkas CSV
php artisan test --filter=ErrorPageTest # 404/403 memakai halaman error Inertia
./vendor/bin/pint --test                # pemeriksaan gaya kode
```

Catatan pengujian manual/otomatis:
- Kata sandi akun uji harus dikembalikan setelah pengujian.
- Hindari menyimpan form Penampilan dengan nilai contoh — nilainya persisten dan akan mengubah branding aplikasi.
- Rate limit masuk adalah 5 percobaan gagal per kredensial+IP.

---

## Deployment

1. `composer install --no-dev --optimize-autoloader`
2. `yarn install && yarn build`
3. `php artisan migrate --force`
4. `php artisan storage:link`
5. `php artisan config:cache route:cache view:cache` (jalankan `php artisan optimize:clear` saat men-debug)
6. Pastikan `storage/` dan `database/` dapat ditulis oleh web server.

---

## Pemecahan Masalah

| Gejala | Penyebab & Solusi |
| --- | --- |
| Perubahan `.vue`/`.css` tidak muncul | Aset belum dikompilasi — jalankan `yarn build` (atau `yarn dev`) |
| Branding tidak berubah setelah edit DB | Cache branding — `php artisan cache:clear` |
| Logo tampil sebagai ikon | Berkas tidak dapat dimuat (driver penyimpanan berganti / berkas terhapus) — unggah ulang; ini perilaku fallback yang disengaja |
| Gambar aset 404 setelah pindah driver | Nilai lama tanpa awalan disk; unggah ulang aset agar tersimpan sebagai `local:`/`s3:` |
| `php: not found` | Instal PHP 8.2+ beserta ekstensi pada bagian Persyaratan |
| Izin baru tidak langsung berlaku | Cache Spatie — controller sudah memanggil `forgetCachedPermissions()`; bila mengubah lewat tinker jalankan `app(PermissionRegistrar::class)->forgetCachedPermissions()` |
| Impor ditolak "harus berformat Excel" | Simpan berkas sebagai `.xlsx` (Excel/LibreOffice/Google Sheets → Unduh sebagai Excel); `.csv` tidak lagi didukung |
| Waktu relatif berbahasa Inggris | Setel `APP_LOCALE=id` (locale Carbon mengikuti nilai ini) |
| Login selalu 419 saat dibuka di iframe | Cookie sesi diblokir lintas situs — setel `SESSION_SAME_SITE=none` + `SESSION_SECURE_COOKIE=true`, lalu `php artisan config:clear` |
| `/telescope` mengembalikan 401 | `laravel/sentinel` memblokir akses proxy publik saat `APP_ENV=local` — pastikan `TelescopeServiceProvider::boot()` mendaftarkan ulang grup middleware `telescope` (lihat bagian Telescope) |
| Username numerik tidak bisa masuk | Sudah diperbaiki: kredensial dicari serentak pada `email`/`username`/`phone`, bukan ditebak dari formatnya |
| Pesan validasi masih berbahasa Inggris | Jalankan `php artisan lang:add id` lalu `php artisan optimize:clear` |

---

## Lisensi

MIT.
