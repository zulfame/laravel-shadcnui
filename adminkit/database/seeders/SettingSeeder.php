<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Setelan aplikasi: identitas merek, SEO, kontak, dan preferensi tampilan. */
class SettingSeeder extends Seeder
{
    private const SETTINGS = [
        'app_name' => 'CODEX',
        'app_url' => null,
        'brand_color' => '#0F0F0F',
        'brand_initials' => '</>',
        'canonical_url' => 'https://codex.test',
        'company' => 'AdminKit',
        'date_format' => 'DD/MM/YYYY',
        'favicon' => null,
        'footer_text' => '@ 2026 CV Jendela Kreasi Visual. All Rights Reserved.',
        'language' => 'id',
        'logo_dark' => null,
        'logo_light' => null,
        'meta_description' => 'CODEX (Core Data Exchange) adalah platform pertukaran data terpusat untuk mengintegrasikan dan mengelola data antar sistem secara aman dan efisien.',
        'meta_keywords' => 'codex, admin panel, laravel, inertia, vue',
        'meta_title' => 'CODEX: Core Data Exchange',
        'og_description' => 'CODEX adalah platform integrasi data inti untuk monitoring, analisis, dan pertukaran data nasabah BPR Bangunarta.',
        'og_image' => null,
        'og_title' => 'CODEX: Core Data Exchange',
        'permission_entity_order' => '["dashboard","profile","permissions","roles","users","appearance","activity"]',
        'search_indexable' => '0',
        'support_email' => 'studio@jkv.co.id',
        'tagline' => 'Core Data Exchange',
        'thumbnail' => 'branding/Evm4ywAi7xnIlGuTyYvTDAuTSwLSOZErKyoKBFDP.jpg',
        'timezone' => 'Asia/Jakarta',
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
