# Auth Testing — AdminKit (Laravel 12 + Inertia)

Autentikasi memakai **session guard bawaan Laravel** (bukan JWT) + `spatie/laravel-permission`.
Aplikasi disajikan di port 3000 oleh `php artisan serve` (supervisor: program `frontend`).
Direktori aplikasi: `/app/adminkit`.

## Kredensial

| Kolom | Nilai |
|---|---|
| Nama | Zulfadli Rizal |
| Username | zulfame |
| Email | zulfadlirizal@gmail.com |
| Telepon | 082320099971 |
| Kata sandi | password |
| Peranan | Super Admin |

Field **Kredensial** menerima ketiganya: email, username, atau nomor telepon.

## Verifikasi basis data (SQLite)

```bash
cd /app/adminkit
php artisan tinker --execute="\$u=App\Models\User::first(); dump(\$u->only(['name','username','email','phone']), \$u->getRoleNames()->all(), str_starts_with(\$u->password,'\$2y\$'));"
sqlite3 database/database.sqlite "select name from sqlite_master where type='table';"
```

Harus terlihat: tabel `roles`, `permissions`, `model_has_roles`; hash kata sandi berawalan `$2y$` (bcrypt); peranan `Super Admin`.

## Yang harus diuji

1. `GET /` tanpa sesi → **302** ke `/login` (middleware `auth`).
2. `GET /login` dengan sesi aktif → **302** ke `/` (middleware `guest`).
3. Login dengan **username**, **email**, dan **nomor telepon** — ketiganya berhasil.
4. Kata sandi salah → error inline `[data-testid="login-form-error"]` berbunyi "Kredensial atau kata sandi tidak cocok."
5. **Rate limit**: 5 kali gagal untuk kredensial+IP yang sama → pesan "Terlalu banyak percobaan masuk. Silakan coba lagi dalam N detik." (`RateLimiter`, lihat `LoginRequest::ensureIsNotRateLimited`).
6. Login sukses → redirect ke `/`, toast `[data-testid="toast-success"]` "Selamat datang kembali, …", sidebar menampilkan nama & email pengguna.
7. `is_active = false` → login ditolak (kondisi ada di `Auth::attempt`).
8. Logout (`[data-testid="logout-button"]`) → sesi diinvalidasi, kembali ke `/login`.
9. Halaman `/profile` menampilkan nama, email, telepon, kantor, dan badge peranan **Super Admin** dari data pengguna yang login (bukan data dummy).
10. Kolom `last_login_at` terisi setelah login berhasil.

## Reset data uji

```bash
cd /app/adminkit && php artisan migrate:fresh --seed --force
```

Seeder **idempoten** (`updateOrCreate` + `Role::findOrCreate`), aman dijalankan berulang.

## data-testid halaman login

`login-credential-input` · `login-password-input` · `login-password-input-toggle` ·
`login-remember-checkbox` · `login-submit-button` · `login-form-error` ·
`login-forgot-password` (belum berfungsi) · `auth-theme-toggle` · `auth-illustration`
