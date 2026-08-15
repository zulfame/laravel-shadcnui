# Catatan Lingkungan — AdminKit

## PHP hilang setelah pod restart (terjadi 2026-06-15)
Gejala: supervisor `frontend` BACKOFF, log `php: not found`, preview 502.
Perbaikan (jalankan di background, ±2 menit):

```bash
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq lsb-release ca-certificates apt-transport-https gnupg curl
curl -sSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/deb.sury.org-php.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/sury-php.list
apt-get update -qq
apt-get install -y -qq php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl
sudo supervisorctl restart frontend
```

Catatan:
- `vendor/` dan `database/database.sqlite` ada di `/app/adminkit` → persist, tidak perlu composer install ulang.
- Server preview = `php artisan serve --port 3000` lewat script `start` di `/app/frontend/package.json`.
- Composer TIDAK terpasang; instal hanya jika perlu menambah paket PHP.
