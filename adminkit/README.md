# CODEX — AdminKit Starter Kit

Starter kit panel admin **compact UI** yang siap dikembangkan: Laravel 12 + Vue 3 + Inertia.js + TailwindCSS 3 di atas SQLite, lengkap dengan autentikasi, hak akses berbasis peranan, audit trail, pengaturan penampilan, dan pengaturan penyimpanan (S3).

> Design system mengikuti **FlowDesk** (compact, monokrom) dengan komponen porting **shadcn/ui** dan dukungan **dark mode**.

---

## Daftar Isi

- [Tumpukan Teknologi](#tumpukan-teknologi)
- [Fitur](#fitur)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Perintah Harian](#perintah-harian)
- [Struktur Proyek](#struktur-proyek)
- [Standar Validasi (WAJIB)](#standar-validasi-wajib)
- [Hak Akses & Peranan](#hak-akses--peranan)
- [Audit Trail](#audit-trail)
- [Pengaturan Penampilan (Branding)](#pengaturan-penampilan-branding)
- [Pengaturan Penyimpanan (S3)](#pengaturan-penyimpanan-s3)
- [Design System & Konvensi UI](#design-system--konvensi-ui)
- [Tabel Server-side](#tabel-server-side)
- [Rute](#rute)
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
| Penyimpanan | Disk `public` (lokal) atau S3 via `league/flysystem-aws-s3-v3` |
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
- `username`, `email`, `phone` opsional namun **unik**; nomor HP hanya menerima angka (boleh `+`).

**Peranan**
- CRUD peranan, peranan `Super Admin` terkunci dari perubahan/penghapusan.
- **Impor peranan dari CSV** (satu nama per baris, header `name` diabaikan, duplikat dilewati).
- Halaman detail peranan (placeholder untuk pengaturan hak akses lanjutan).

**Audit Trail**
- Mencatat siapa mengubah apa, **diff nilai sebelum → sesudah**, konteks permintaan, dan kegagalan sistem.
- Halaman detail khusus pengembang termasuk **Payload Mentah (JSON)**.
- Hapus jejak audit berdasarkan rentang tanggal.

**Pengaturan**
- **Penampilan**: identitas aplikasi (nama, tagline, inisial brand), logo terang/gelap, favicon, SEO & metadata (termasuk Open Graph), kontak & footer.
- **Penyimpanan**: pilih driver `local`/`s3`, kredensial S3, prefix path, path-style endpoint, dan **Uji Koneksi**.

**UI**
- Dark mode, sidebar dapat di-collapse (mode ikon), breadcrumb otomatis, toast, dialog, combobox dengan pencarian, date picker.
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

cp .env.example .env          # bila belum ada .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed    # membuat peranan, izin, akun awal, dan setelan
php artisan storage:link      # agar berkas unggahan lokal dapat diakses

yarn build                    # atau: yarn dev (mode pengembangan)
php artisan serve
```

Variabel `.env` yang relevan:

```env
APP_LOCALE=id                 # memengaruhi format tanggal & waktu relatif
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=sqlite
DB_DATABASE=/abs/path/database/database.sqlite
```

Akun awal dibuat oleh `database/seeders/DatabaseSeeder.php` (lihat berkas tersebut untuk kredensial). **Ganti kata sandi setelah instalasi.**

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
│   ├── Controllers/                    # Auth, Dashboard, User, Role, Profile, Appearance, Storage, ActivityLog
│   ├── Middleware/HandleInertiaRequests.php   # share auth, branding, flash
│   └── Requests/                       # SATU Form Request per form (lihat Standar Validasi)
├── Models/                             # User, Role (Spatie), ActivityLog, Setting
├── Providers/AppServiceProvider.php    # branding untuk blade root + locale Carbon
└── Support/
    ├── Branding.php                    # pembacaan setelan branding (+cache)
    ├── FileStorage.php                 # satu pintu unggahan (local/s3, prefix disk)
    ├── Modules.php                      # daftar modul & izin
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
│   └── pages/                          # Dashboard, Users, Roles, RoleDetail, AuditTrail, AuditDetail, Appearance, Storage, Profile, auth/Login
└── views/app.blade.php                 # root blade (judul, favicon, meta SEO/OG)

routes/web.php
database/{migrations,seeders,factories}
```

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

## Pengaturan Penampilan (Branding)

- Nilai disimpan pada tabel `settings` dan dibaca lewat `App\Support\Branding` (`raw()` untuk form, `values()` untuk tampilan) — hasilnya **di-cache**, jadi jalankan `php artisan cache:clear` bila mengubah langsung dari DB.
- Kunci bertipe aset: `logo_light`, `logo_dark`, `favicon`, `og_image` (lihat `Branding::ASSETS`).
- Urutan tampil merek (`BrandMark.vue`): **logo → inisial brand → ikon**. Bila berkas logo gagal dimuat (mis. driver penyimpanan berganti), komponen otomatis jatuh ke inisial/ikon sehingga tidak pernah tampil "gambar rusak".
- `resources/views/app.blade.php` memakai nilai ini untuk `<title>`, favicon, meta description/keywords, canonical, `noindex`, dan Open Graph.

---

## Pengaturan Penyimpanan (S3)

- Halaman `/storage-settings` mengatur `storage_driver` (`local`/`s3`), endpoint, bucket, **path** (prefix folder), access key, secret, region, URL publik, dan path-style endpoint.
- Semua unggahan melewati `App\Support\FileStorage`:
  - Nilai path yang disimpan **berawalan disk**, mis. `local:branding/x.png` atau `s3:adminkit/branding/x.png`, sehingga berkas lama tetap dapat diakses walau driver aktif diganti.
  - `FileStorage::url()` membangun URL publik (memakai `s3_public_url` bila diisi).
- Secret **tidak pernah** dikirim balik ke antarmuka; biarkan kosong bila tidak ingin menggantinya.
- Tombol **Uji Koneksi** menulis lalu menghapus berkas percobaan.

---

## Design System & Konvensi UI

- Token warna, densitas, dan tipografi ada di `resources/css/app.css` (`--ctl-h`, `--field-gap`, `--item-gap`, `.form-dense`, `.tbl-density`).
- **Badge** memakai referensi asli shadcn/ui (`default`, `secondary`, `destructive`, `outline`) dengan padding compact — tanpa palet warna kustom.
- **Label dan judul memakai Title Case** (mis. `Nama Pengguna`, `Kata Sandi`, `Tambah Pengguna`), bukan Sentence case.
- Jangan menambahkan `CardDescription` di bawah judul kartu.
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
| GET | `/roles`, `/roles/{role}` | Daftar & detail peranan |
| POST/PUT/DELETE | `/roles`, `/roles/{role}` | CRUD peranan |
| POST | `/roles/import` | Impor peranan dari CSV |
| GET | `/audit-trail`, `/audit-trail/{log}` | Audit trail & detail |
| DELETE | `/audit-trail` | Hapus jejak audit pada rentang tanggal |
| GET | `/appearance` | Pengaturan penampilan |
| PUT | `/appearance/{identity\|seo\|contact}` | Simpan per bagian |
| POST/DELETE | `/appearance/asset/{key}` | Unggah/hapus aset merek |
| GET/PUT | `/storage-settings` | Pengaturan penyimpanan |
| POST | `/storage-settings/test` | Uji koneksi S3 |

---

## Pengujian

```bash
php artisan test          # bila suite Pest/PHPUnit ditambahkan
./vendor/bin/pint --test  # pemeriksaan gaya kode
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
| Waktu relatif berbahasa Inggris | Setel `APP_LOCALE=id` (locale Carbon mengikuti nilai ini) |

---

## Lisensi

MIT.
