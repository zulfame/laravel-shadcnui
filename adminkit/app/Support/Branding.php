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

    /** Kunci bertipe aset berkas (disimpan sebagai path pada disk `public`). */
    public const ASSETS = ['logo_light', 'logo_dark', 'favicon', 'thumbnail', 'og_image'];

    public const DEFAULTS = [
        'app_name' => 'AdminKit',
        'tagline' => 'Admin Panel Starter Kit',
        'brand_initials' => 'AK',
        'company' => 'AdminKit',
        'app_url' => '',
        'timezone' => 'Asia/Jakarta',
        'language' => 'id',
        'date_format' => 'DD/MM/YYYY',
        'brand_color' => '#0F0F0F',
        'meta_description' => 'Starter kit panel admin: compact, monokrom, dan siap dikembangkan.',
        'meta_keywords' => 'admin panel, laravel, inertia, vue',
        'canonical_url' => '',
        'search_indexable' => '0',
        'og_title' => '',
        'og_description' => '',
        'support_email' => 'dukungan@adminkit.test',
        'footer_text' => '',
        'logo_light' => null,
        'logo_dark' => null,
        'favicon' => null,
        'thumbnail' => null,
        'og_image' => null,
    ];

    /** Nilai siap pakai (aset sudah menjadi URL publik). */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = Schema::hasTable('settings') ? Setting::values() : [];
            $values = array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));

            foreach (self::ASSETS as $key) {
                $values[$key.'_path'] = $values[$key] ?: null;
                $values[$key] = FileStorage::url($values[$key]);
            }

            $values['search_indexable'] = (bool) ($values['search_indexable'] ?? false);
            $values['footer_text'] = $values['footer_text']
                ?: '© '.date('Y').' '.$values['company'].'. All Rights Reserved.';
            $values['og_title'] = $values['og_title'] ?: $values['app_name'].': '.$values['tagline'];
            $values['og_description'] = $values['og_description'] ?: $values['meta_description'];

            return $values;
        });
    }

    /** Nilai mentah untuk form (tanpa fallback turunan). */
    public static function raw(): array
    {
        $stored = Schema::hasTable('settings') ? Setting::values() : [];
        $values = array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));

        foreach (self::ASSETS as $key) {
            $values[$key.'_url'] = FileStorage::url($values[$key]);
        }
        $values['search_indexable'] = (bool) ($values['search_indexable'] ?? false);

        return $values;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
