<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Branding dinamis: nilai dari tabel `settings` ditimpakan ke default.
 * Dipakai oleh Inertia share (antarmuka) dan blade root (meta/SEO).
 */
class Branding
{
    public const CACHE_KEY = 'adminkit.branding';

    /** Kunci bertipe aset berkas (disimpan sebagai path pada disk aktif). */
    public const ASSETS = ['logo_light', 'logo_dark', 'favicon', 'og_image'];

    public const DEFAULTS = [
        'app_name' => 'AdminKit',
        'tagline' => 'Admin Panel Starter Kit',
        'brand_initials' => 'AK',
        'language' => 'id',
        'meta_description' => 'Starter kit panel admin: compact, monokrom, dan siap dikembangkan.',
        'meta_keywords' => 'admin panel, laravel, inertia, vue',
        'canonical_url' => '',
        'search_indexable' => '0',
        'meta_title' => '',
        'support_email' => 'dukungan@adminkit.test',
        'footer_text' => '',
        'logo_light' => null,
        'logo_dark' => null,
        'favicon' => null,
        'og_image' => null,
    ];

    /** Nilai siap pakai (aset sudah menjadi URL publik). */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $values = self::merged();

            foreach (self::ASSETS as $key) {
                $values[$key.'_path'] = $values[$key] ?: null;
                $values[$key] = FileStorage::url($values[$key]);
            }

            $values['search_indexable'] = (bool) ($values['search_indexable'] ?? false);

            return $values;
        });
    }

    /** Nilai mentah untuk form (tanpa fallback turunan). */
    public static function raw(): array
    {
        $values = self::merged();

        foreach (self::ASSETS as $key) {
            $values[$key.'_url'] = FileStorage::url($values[$key]);
        }
        $values['search_indexable'] = (bool) ($values['search_indexable'] ?? false);

        return $values;
    }

    /**
     * Gabungkan default dengan nilai tersimpan. Nilai yang SENGAJA dikosongkan
     * tetap kosong (tidak jatuh ke default) — default hanya untuk kunci yang
     * belum pernah diatur.
     */
    private static function merged(): array
    {
        $stored = Schema::hasTable('settings') ? Setting::values() : [];
        $values = array_merge(self::DEFAULTS, $stored);

        foreach ($values as $key => $value) {
            if ($value === null && ! in_array($key, self::ASSETS, true)) {
                $values[$key] = '';
            }
        }

        return $values;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
