<?php

namespace App\Support;

use App\Http\Controllers\StorageController;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Satu pintu untuk seluruh unggahan berkas (avatar, aset merek).
 * Driver mengikuti setelan di halaman Penyimpanan: `local` atau `s3`.
 *
 * Nilai yang disimpan di DB memakai awalan disk, mis. `s3:branding/x.png`,
 * sehingga berkas lama tetap dapat diakses meski driver aktif berganti.
 */
class FileStorage
{
    public static function driver(): string
    {
        return StorageController::values()['storage_driver'] ?? 'local';
    }

    public static function disk(?string $driver = null): Filesystem
    {
        if (($driver ?? self::driver()) !== 's3') {
            return Storage::disk('public');
        }

        $v = StorageController::values();

        return Storage::build([
            'driver' => 's3',
            'key' => $v['s3_key'],
            'secret' => $v['s3_secret'],
            'region' => $v['s3_region'],
            'bucket' => $v['s3_bucket'],
            'endpoint' => $v['s3_endpoint'],
            'use_path_style_endpoint' => (bool) $v['s3_path_style'],
            'visibility' => 'public',
            'throw' => true,
        ]);
    }

    public static function store(UploadedFile $file, string $folder): string
    {
        $driver = self::driver();

        return $driver.':'.self::disk($driver)->putFile(self::prefix($folder, $driver), $file);
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

        $v = StorageController::values();
        $base = $v['s3_public_url'] ?: rtrim($v['s3_endpoint'], '/').'/'.$v['s3_bucket'];

        return rtrim($base, '/').'/'.ltrim($path, '/');
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

    /** Prefix folder dengan `s3_path` bila driver s3 memakainya. */
    private static function prefix(string $folder, string $driver): string
    {
        if ($driver !== 's3') {
            return $folder;
        }

        $path = trim((string) (StorageController::values()['s3_path'] ?? ''), '/');

        return $path === '' ? $folder : "{$path}/{$folder}";
    }
}
