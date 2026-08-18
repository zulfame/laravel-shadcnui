#!/usr/bin/env bash
# Pod restart menghapus /usr & /usr/local → PHP dan symlink composer hilang.
# Jalankan: bash /app/memory/restore_php.sh  (±4-6 menit, jalankan di background)
set -e
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq lsb-release ca-certificates apt-transport-https gnupg curl
curl -sSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/deb.sury.org-php.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/sury-php.list
apt-get update -qq
apt-get install -y -qq php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl
printf 'upload_max_filesize = 64M\npost_max_size = 64M\nmax_execution_time = 300\nmemory_limit = 512M\n' > /etc/php/8.3/cli/conf.d/99-adminkit.ini
ln -sf /app/bin/composer /usr/local/bin/composer   # composer.phar disimpan di /app/bin (persisten)
sudo supervisorctl restart frontend
