<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storage\DeleteObjectRequest;
use App\Http\Requests\Storage\RenameObjectRequest;
use App\Http\Requests\Storage\UploadObjectRequest;
use App\Models\ActivityLog;
use App\Support\FileStorage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengelola berkas pada object storage aktif (disk dari `FILESYSTEM_DISK`).
 * Semua operasi memakai App\Support\FileStorage agar konsisten dengan modul lain.
 */
class ObjectStorageController extends Controller
{
    /** Pengelompokan jenis media untuk pratinjau & label. */
    private const KINDS = [
        'gambar' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif', 'bmp', 'ico'],
        'video' => ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'],
        'dokumen' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'md'],
        'arsip' => ['zip', 'rar', '7z', 'tar', 'gz'],
    ];

    private function disk(): Filesystem
    {
        return FileStorage::disk();
    }

    public function index(Request $request): Response
    {
        $folder = trim((string) $request->query('folder', ''), '/');
        $search = mb_strtolower(trim((string) $request->query('search', '')));

        $paths = collect($this->disk()->allFiles($folder ?: null));

        $files = $paths
            ->filter(fn (string $path) => $search === '' || str_contains(mb_strtolower($path), $search))
            ->sortDesc()
            ->take(300)
            ->map(fn (string $path) => $this->describe($path))
            ->values()->all();

        return Inertia::render('ObjectStorage', [
            'folders' => $paths
                ->map(fn (string $path) => str_contains($path, '/') ? str($path)->beforeLast('/')->value() : '')
                ->unique()->filter()->sort()->values()
                ->map(fn (string $name) => ['value' => $name, 'label' => $name])
                ->prepend(['value' => '', 'label' => 'Semua Folder'])
                ->all(),
            'files' => $files,
            'query' => ['folder' => $folder, 'search' => $request->query('search', '')],
        ]);
    }

    public function store(UploadObjectRequest $request): RedirectResponse
    {
        $folder = trim((string) $request->validated('folder'), '/') ?: 'uploads';
        $stored = [];

        foreach ($request->file('files') as $file) {
            $stored[] = FileStorage::storeReadable($file, $folder);
        }

        ActivityLog::record(
            count($stored).' berkas diunggah ke '.$folder,
            'Object Storage',
            'success',
            context: ['berkas' => $stored],
        );

        return back()->with('success', count($stored).' berkas berhasil diunggah.');
    }

    /** Ganti nama / pindahkan berkas dalam disk yang sama. */
    public function rename(RenameObjectRequest $request): RedirectResponse
    {
        ['path' => $path, 'target' => $target] = $request->validated();

        $this->disk()->move($path, $target);

        ActivityLog::record("Mengubah nama berkas {$path}", 'Object Storage', 'info', context: ['menjadi' => $target]);

        return back()->with('success', 'Nama berkas diperbarui.');
    }

    public function destroy(DeleteObjectRequest $request): RedirectResponse
    {
        $paths = $request->validated()['paths'];

        $this->disk()->delete($paths);

        ActivityLog::record(
            count($paths).' berkas dihapus dari penyimpanan',
            'Object Storage',
            'danger',
            context: ['berkas' => $paths],
        );

        return back()->with('success', count($paths).' berkas dihapus.');
    }

    private function describe(string $path): array
    {
        $extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $kind = 'lainnya';

        foreach (self::KINDS as $name => $extensions) {
            if (in_array($extension, $extensions, true)) {
                $kind = $name;

                break;
            }
        }

        return [
            'path' => $path,
            'name' => basename($path),
            'folder' => str_contains($path, '/') ? str($path)->beforeLast('/')->value() : '',
            'extension' => $extension,
            'kind' => $kind,
            'is_image' => $kind === 'gambar',
            'size' => $this->disk()->size($path),
            'modified_at' => now()->setTimestamp($this->disk()->lastModified($path))->translatedFormat('d M Y, H.i'),
            'url' => FileStorage::url(FileStorage::driver().':'.$path),
        ];
    }
}
