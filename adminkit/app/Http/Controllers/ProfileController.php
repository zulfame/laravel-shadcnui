<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\ActivityLog;
use App\Support\FileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        ActivityLog::record('Memperbarui profil sendiri', 'Profil', 'info', $request->user());

        return back()->with('success', 'Profil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated()['password']),
        ]);

        ActivityLog::record('Mengubah kata sandi sendiri', 'Keamanan', 'warning', $request->user());

        return back()->with('success', 'Kata sandi diperbarui.');
    }

    /**
     * Simpan foto profil ke disk `public` (storage/app/public/avatars).
     */
    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();

        FileStorage::delete($user->avatar);

        $user->update([
            'avatar' => FileStorage::store($request->file('avatar'), 'avatars'),
        ]);

        ActivityLog::record('Mengubah foto profil', 'Profil', 'info', $user);

        return back()->with('success', 'Foto profil diperbarui.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            FileStorage::delete($user->avatar);
            $user->update(['avatar' => null]);
            ActivityLog::record('Menghapus foto profil', 'Profil', 'info', $user);
        }

        return back()->with('success', 'Foto profil dihapus.');
    }
}
