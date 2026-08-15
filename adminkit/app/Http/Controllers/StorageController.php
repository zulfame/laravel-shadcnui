<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorageSetting\UpdateStorageRequest;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StorageController extends Controller
{
    /** Kunci setelan penyimpanan + nilai default (diisi seeder). */
    public const KEYS = [
        'storage_driver' => 'local',
        's3_endpoint' => '',
        's3_key' => '',
        's3_secret' => '',
        's3_region' => '',
        's3_bucket' => '',
        's3_path' => '',
        's3_path_style' => '1',
        's3_public_url' => '',
    ];

    public static function values(): array
    {
        return array_merge(self::KEYS, array_filter(Setting::values(), fn ($v) => $v !== null));
    }

    public function edit(): Response
    {
        $values = self::values();

        return Inertia::render('Storage', [
            'settings' => [
                'storage_driver' => $values['storage_driver'],
                's3_endpoint' => $values['s3_endpoint'],
                's3_key' => $values['s3_key'],
                's3_secret_set' => filled($values['s3_secret']),
                's3_region' => $values['s3_region'],
                's3_bucket' => $values['s3_bucket'],
                's3_path' => $values['s3_path'],
                's3_path_style' => (bool) $values['s3_path_style'],
                's3_public_url' => $values['s3_public_url'],
            ],
            'driverOptions' => [
                ['value' => 'local', 'label' => 'Lokal (storage/app/public)'],
                ['value' => 's3', 'label' => 'Object Storage (S3)'],
            ],
        ]);
    }

    public function update(UpdateStorageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Secret kosong = jangan ubah nilai yang sudah tersimpan.
        if (blank($data['s3_secret'] ?? null)) {
            unset($data['s3_secret']);
        }
        $data['s3_path'] = trim((string) ($data['s3_path'] ?? ''), '/');
        $data['s3_path_style'] = ($data['s3_path_style'] ?? false) ? '1' : '0';

        $changes = Setting::putMany($data);

        ActivityLog::record('Memperbarui pengaturan penyimpanan', 'Penyimpanan', 'info', changes: $changes);

        return back()->with('success', 'Pengaturan penyimpanan disimpan.');
    }

    /**
     * Uji koneksi: tulis lalu hapus satu berkas kecil di bucket.
     */
    public function test(): RedirectResponse
    {
        $v = self::values();

        try {
            $disk = Storage::build([
                'driver' => 's3',
                'key' => $v['s3_key'],
                'secret' => $v['s3_secret'],
                'region' => $v['s3_region'],
                'bucket' => $v['s3_bucket'],
                'endpoint' => $v['s3_endpoint'],
                'use_path_style_endpoint' => (bool) $v['s3_path_style'],
                'throw' => true,
            ]);

            $path = 'adminkit-healthcheck/'.Str::random(12).'.txt';
            $disk->put($path, 'ok');
            $disk->delete($path);
        } catch (\Throwable $e) {
            ActivityLog::record('Uji koneksi penyimpanan gagal', 'Penyimpanan', 'danger');

            return back()->with('error', 'Koneksi gagal: '.Str::limit($e->getMessage(), 160));
        }

        ActivityLog::record('Uji koneksi penyimpanan berhasil', 'Penyimpanan', 'success');

        return back()->with('success', 'Koneksi ke object storage berhasil.');
    }
}
