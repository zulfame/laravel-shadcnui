<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appearance\UpdateContactRequest;
use App\Http\Requests\Appearance\UpdateIdentityRequest;
use App\Http\Requests\Appearance\UpdateSeoRequest;
use App\Http\Requests\Appearance\UploadAssetRequest;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\FileStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Appearance', [
            'settings' => Branding::raw(),
        ]);
    }

    public function updateIdentity(UpdateIdentityRequest $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function updateSeo(UpdateSeoRequest $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function updateContact(UpdateContactRequest $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function uploadAsset(UploadAssetRequest $request, string $key): RedirectResponse
    {
        abort_unless(in_array($key, Branding::ASSETS, true), 404);

        FileStorage::delete(Branding::raw()[$key] ?? null);

        $changes = Setting::putMany(
            [$key => FileStorage::store($request->file('file'), 'branding')],
            allowNull: true,
        );
        Branding::forget();

        ActivityLog::record("Mengunggah aset merek ({$key})", 'Penampilan', 'info', changes: $changes);

        return back()->with('success', 'Aset diunggah.');
    }

    public function destroyAsset(string $key): RedirectResponse
    {
        abort_unless(in_array($key, Branding::ASSETS, true), 404);

        FileStorage::delete(Branding::raw()[$key] ?? null);

        $changes = Setting::putMany([$key => null], allowNull: true);
        Branding::forget();

        ActivityLog::record("Menghapus aset merek ({$key})", 'Penampilan', 'warning', changes: $changes);

        return back()->with('success', 'Aset dihapus.');
    }

    private function persist(FormRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('search_indexable', $data)) {
            $data['search_indexable'] = $data['search_indexable'] ? '1' : '0';
        }

        $changes = Setting::putMany($data);
        Branding::forget();

        ActivityLog::record('Memperbarui pengaturan penampilan', 'Penampilan', 'info', changes: $changes);

        return back()->with('success', 'Pengaturan penampilan disimpan.');
    }
}
