<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Satu pintu untuk seluruh unggahan berkas (avatar, aset merek).
 * Driver mengikuti konfigurasi .env (`FILESYSTEM_DISK=local|s3`) beserta
 * kredensial `AWS_*` pada `config/filesystems.php`.
 *
 * Nilai yang disimpan di DB memakai awalan disk, mis. `s3:branding/x.png`,
 * sehingga berkas lama tetap dapat diakses meski driver aktif berganti.
 */
class FileStorage
{
    public static function driver(): string
    {
        return config('filesystems.default') === 's3' ? 's3' : 'local';
    }

    public static function disk(?string $driver = null): Filesystem
    {
        return Storage::disk(($driver ?? self::driver()) === 's3' ? 's3' : 'public');
    }

    public static function store(UploadedFile $file, string $folder): string
    {
        $driver = self::driver();

        return $driver.':'.self::disk($driver)->putFile($folder, $file);
    }

    public static function delete(?string $value): void
    {
        if (! $value) {
            return;
        }

        [$driver, $path] = self::split($value);

        rescue(fn () => self::disk($driver)->delete($path), report: false);
    }

    /** URL publik berkas. */
    public static function url(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        [$driver, $path] = self::split($value);

        if ($driver !== 's3') {
            return Storage::url($path);
        }

        $config = config('filesystems.disks.s3');
        $base = $config['url'] ?: rtrim((string) $config['endpoint'], '/').'/'.$config['bucket'];
        $prefix = trim((string) ($config['root'] ?? ''), '/');

        return rtrim($base, '/').'/'.($prefix === '' ? '' : $prefix.'/').ltrim($path, '/');
    }

    /** Pisahkan awalan disk; nilai lama tanpa awalan memakai driver aktif. */
    private static function split(string $value): array
    {
        foreach (['local', 's3'] as $driver) {
            if (str_starts_with($value, $driver.':')) {
                return [$driver, substr($value, strlen($driver) + 1)];
            }
        }

        return [self::driver(), $value];
    }
}
