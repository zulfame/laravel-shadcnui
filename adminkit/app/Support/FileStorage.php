<?php

namespace App\Support;

use App\Http\Controllers\StorageController;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Satu pintu untuk seluruh unggahan berkas (avatar, aset merek).
 * Driver mengikuti setelan di halaman Penyimpanan: `local` atau `s3`.
 */
class FileStorage
{
    public static function driver(): string
    {
        return StorageController::values()['storage_driver'] ?? 'local';
    }

    public static function disk(): Filesystem
    {
        if (self::driver() !== 's3') {
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
        return self::disk()->putFile(self::prefix($folder), $file);
    }

    /** Prefix folder dengan `s3_path` bila driver s3 memakainya. */
    private static function prefix(string $folder): string
    {
        if (self::driver() !== 's3') {
            return $folder;
        }

        $path = trim((string) (StorageController::values()['s3_path'] ?? ''), '/');

        return $path === '' ? $folder : "{$path}/{$folder}";
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        rescue(fn () => self::disk()->delete($path), report: false);
    }

    /** URL publik berkas; jatuh ke disk lokal bila driver bukan s3. */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (self::driver() !== 's3') {
            return Storage::url($path);
        }

        $v = StorageController::values();
        $base = $v['s3_public_url'] ?: rtrim($v['s3_endpoint'], '/').'/'.$v['s3_bucket'];

        return rtrim($base, '/').'/'.ltrim($path, '/');
    }
}
