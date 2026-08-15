<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\FileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    /** Aturan validasi per bagian kartu. */
    private const SECTIONS = [
        'identity' => [
            'app_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:100'],
            'brand_initials' => ['nullable', 'string', 'max:4'],
            'company' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'language' => ['required', 'in:id,en'],
            'date_format' => ['required', 'in:DD/MM/YYYY,YYYY-MM-DD,DD MMM YYYY'],
            'app_url' => ['nullable', 'url', 'max:200'],
        ],
        'brand' => [
            'brand_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ],
        'seo' => [
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords' => ['nullable', 'string', 'max:200'],
            'canonical_url' => ['nullable', 'url', 'max:200'],
            'search_indexable' => ['boolean'],
        ],
        'og' => [
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
        ],
        'contact' => [
            'support_email' => ['nullable', 'email', 'max:150'],
            'footer_text' => ['nullable', 'string', 'max:200'],
        ],
    ];

    public function edit(): Response
    {
        return Inertia::render('Appearance', [
            'settings' => Branding::raw(),
            'timezones' => collect(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC'])
                ->map(fn ($tz) => ['value' => $tz, 'label' => $tz])->all(),
            'languages' => [
                ['value' => 'id', 'label' => 'Indonesia'],
                ['value' => 'en', 'label' => 'English'],
            ],
            'dateFormats' => [
                ['value' => 'DD/MM/YYYY', 'label' => 'DD/MM/YYYY'],
                ['value' => 'YYYY-MM-DD', 'label' => 'YYYY-MM-DD'],
                ['value' => 'DD MMM YYYY', 'label' => 'DD MMM YYYY'],
            ],
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);

        $data = $request->validate(self::SECTIONS[$section]);

        if (array_key_exists('search_indexable', $data)) {
            $data['search_indexable'] = $data['search_indexable'] ? '1' : '0';
        }

        Setting::putMany($data);
        Branding::forget();

        ActivityLog::record('Memperbarui pengaturan penampilan', 'Penampilan', 'info');

        return back()->with('success', 'Pengaturan penampilan disimpan.');
    }

    public function uploadAsset(Request $request, string $key): RedirectResponse
    {
        abort_unless(in_array($key, Branding::ASSETS, true), 404);

        $isFavicon = $key === 'favicon';
        $request->validate([
            'file' => [
                'required',
                'file',
                $isFavicon ? 'mimes:png,ico,svg' : 'image',
                $isFavicon ? 'max:256' : 'max:600',
            ],
        ], [], ['file' => 'berkas']);

        FileStorage::delete(Branding::raw()[$key] ?? null);

        Setting::putMany([$key => FileStorage::store($request->file('file'), 'branding')]);
        Branding::forget();

        ActivityLog::record("Mengunggah aset merek ({$key})", 'Penampilan', 'info');

        return back()->with('success', 'Aset merek diunggah.');
    }

    public function destroyAsset(string $key): RedirectResponse
    {
        abort_unless(in_array($key, Branding::ASSETS, true), 404);

        FileStorage::delete(Branding::raw()[$key] ?? null);

        Setting::putMany([$key => null]);
        Branding::forget();

        return back()->with('success', 'Aset merek dihapus.');
    }
}
