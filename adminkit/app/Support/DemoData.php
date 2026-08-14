<?php

namespace App\Support;

/**
 * Sumber data contoh untuk fase statis.
 *
 * Ketika lapisan basis data diaktifkan, setiap method di sini digantikan oleh
 * query Eloquent pada controller masing-masing.
 */
class DemoData
{
    public static function kpis(): array
    {
        return [
            ['key' => 'users', 'label' => 'Total Pengguna', 'value' => '1.284', 'hint' => '38 bergabung bulan ini'],
            ['key' => 'roles', 'label' => 'Peranan Aktif', 'value' => '6', 'hint' => '42 izin terpetakan'],
            ['key' => 'sessions', 'label' => 'Sesi Aktif', 'value' => '73', 'hint' => 'Puncak 128 pukul 10.00'],
            ['key' => 'storage', 'label' => 'Penyimpanan', 'value' => '18,4 GB', 'hint' => 'dari kuota 50 GB'],
            ['key' => 'queue', 'label' => 'Antrean Tugas', 'value' => '4', 'hint' => '1 gagal perlu ditinjau'],
        ];
    }

    public static function recentUsers(): array
    {
        return [
            ['id' => 1, 'name' => 'Rani Kusuma', 'email' => 'rani.kusuma@adminkit.test', 'role' => 'Editor', 'completeness' => 92, 'status_label' => 'Aktif', 'status_chip' => '--st-done'],
            ['id' => 2, 'name' => 'Budi Santoso', 'email' => 'budi.santoso@adminkit.test', 'role' => 'Administrator', 'completeness' => 100, 'status_label' => 'Aktif', 'status_chip' => '--st-done'],
            ['id' => 3, 'name' => 'Sinta Larasati', 'email' => 'sinta.l@adminkit.test', 'role' => 'Staf', 'completeness' => 64, 'status_label' => 'Menunggu', 'status_chip' => '--st-pending'],
            ['id' => 4, 'name' => 'Agus Wibowo', 'email' => 'agus.wibowo@adminkit.test', 'role' => 'Auditor', 'completeness' => 48, 'status_label' => 'Nonaktif', 'status_chip' => '--st-cancelled'],
            ['id' => 5, 'name' => 'Maya Anggraini', 'email' => 'maya.a@adminkit.test', 'role' => 'Editor', 'completeness' => 76, 'status_label' => 'Aktif', 'status_chip' => '--st-done'],
            ['id' => 6, 'name' => 'Fajar Nugroho', 'email' => 'fajar.n@adminkit.test', 'role' => 'Staf', 'completeness' => 31, 'status_label' => 'Diblokir', 'status_chip' => '--st-overdue'],
            ['id' => 7, 'name' => 'Dewi Puspita', 'email' => 'dewi.p@adminkit.test', 'role' => 'Staf', 'completeness' => 88, 'status_label' => 'Aktif', 'status_chip' => '--st-done'],
        ];
    }

    public static function activities(): array
    {
        return [
            ['id' => 1, 'time' => '09.42', 'action' => 'Memperbarui peranan Editor', 'actor' => 'Budi Santoso', 'module' => 'Peranan'],
            ['id' => 2, 'time' => '09.15', 'action' => 'Menambah pengguna baru', 'actor' => 'Dimas Prakoso', 'module' => 'Pengguna'],
            ['id' => 3, 'time' => '08.58', 'action' => 'Mengunggah logo aplikasi', 'actor' => 'Dimas Prakoso', 'module' => 'Aplikasi'],
            ['id' => 4, 'time' => '08.30', 'action' => 'Gagal masuk 3 kali', 'actor' => 'agus.wibowo', 'module' => 'Keamanan'],
            ['id' => 5, 'time' => '07.55', 'action' => 'Cadangan basis data selesai', 'actor' => 'Sistem', 'module' => 'Database'],
            ['id' => 6, 'time' => '07.20', 'action' => 'Menghapus 12 arsip lama', 'actor' => 'Sistem', 'module' => 'Arsip'],
        ];
    }

    public static function trend(): array
    {
        return [
            ['label' => 'Sen', 'created' => 12, 'active' => 48],
            ['label' => 'Sel', 'created' => 18, 'active' => 61],
            ['label' => 'Rab', 'created' => 9, 'active' => 55],
            ['label' => 'Kam', 'created' => 22, 'active' => 73],
            ['label' => 'Jum', 'created' => 15, 'active' => 68],
            ['label' => 'Sab', 'created' => 5, 'active' => 24],
            ['label' => 'Min', 'created' => 3, 'active' => 17],
        ];
    }

    public static function byModule(): array
    {
        return [
            ['label' => 'Pengguna', 'count' => 148],
            ['label' => 'Peranan', 'count' => 96],
            ['label' => 'Aplikasi', 'count' => 61],
            ['label' => 'Keamanan', 'count' => 44],
            ['label' => 'Database', 'count' => 23],
        ];
    }

    public static function storage(): array
    {
        return [
            ['label' => 'Dokumen', 'used' => '9,2 GB', 'total' => '25 GB', 'percent' => 37],
            ['label' => 'Media', 'used' => '6,8 GB', 'total' => '15 GB', 'percent' => 45],
            ['label' => 'Cadangan', 'used' => '2,4 GB', 'total' => '10 GB', 'percent' => 24],
        ];
    }

    public static function users(): array
    {
        $rows = [
            ['Rani Kusuma', 'rani.kusuma@adminkit.test', 'Editor', 'Konten', 'Aktif', '--st-done', '17 Jun 2026, 09.41'],
            ['Budi Santoso', 'budi.santoso@adminkit.test', 'Administrator', 'Teknologi Informasi', 'Aktif', '--st-done', '17 Jun 2026, 08.12'],
            ['Sinta Larasati', 'sinta.l@adminkit.test', 'Staf', 'Keuangan', 'Menunggu', '--st-pending', '16 Jun 2026, 16.55'],
            ['Agus Wibowo', 'agus.wibowo@adminkit.test', 'Auditor', 'Kepatuhan', 'Nonaktif', '--st-cancelled', '02 Jun 2026, 11.03'],
            ['Maya Anggraini', 'maya.a@adminkit.test', 'Editor', 'Konten', 'Aktif', '--st-done', '17 Jun 2026, 07.48'],
            ['Fajar Nugroho', 'fajar.n@adminkit.test', 'Staf', 'Operasional', 'Diblokir', '--st-overdue', '28 Mei 2026, 20.10'],
            ['Dewi Puspita', 'dewi.p@adminkit.test', 'Staf', 'Sumber Daya Manusia', 'Aktif', '--st-done', '16 Jun 2026, 13.27'],
            ['Hendra Saputra', 'hendra.s@adminkit.test', 'Editor', 'Pemasaran', 'Aktif', '--st-done', '15 Jun 2026, 09.02'],
            ['Lia Marlina', 'lia.m@adminkit.test', 'Auditor', 'Kepatuhan', 'Aktif', '--st-done', '14 Jun 2026, 15.40'],
            ['Rizky Ananda', 'rizky.a@adminkit.test', 'Staf', 'Operasional', 'Menunggu', '--st-pending', '13 Jun 2026, 10.19'],
            ['Nadia Safira', 'nadia.s@adminkit.test', 'Staf', 'Keuangan', 'Aktif', '--st-done', '12 Jun 2026, 08.35'],
            ['Yoga Pratama', 'yoga.p@adminkit.test', 'Administrator', 'Teknologi Informasi', 'Aktif', '--st-done', '11 Jun 2026, 17.21'],
        ];

        return collect($rows)->map(fn ($row, $i) => [
            'id' => $i + 1,
            'name' => $row[0],
            'email' => $row[1],
            'role' => $row[2],
            'department' => $row[3],
            'status_label' => $row[4],
            'status_chip' => $row[5],
            'last_login' => $row[6],
        ])->all();
    }

    public static function roles(): array
    {
        return [
            ['id' => 1, 'slug' => 'administrator', 'name' => 'Administrator', 'description' => 'Akses penuh ke seluruh modul dan pengaturan sistem.', 'users_count' => 3, 'permissions_count' => 42],
            ['id' => 2, 'slug' => 'editor', 'name' => 'Editor', 'description' => 'Mengelola konten dan data operasional harian.', 'users_count' => 18, 'permissions_count' => 24],
            ['id' => 3, 'slug' => 'auditor', 'name' => 'Auditor', 'description' => 'Akses baca dan ekspor untuk keperluan audit.', 'users_count' => 5, 'permissions_count' => 11],
            ['id' => 4, 'slug' => 'staf', 'name' => 'Staf', 'description' => 'Akses terbatas pada modul yang ditugaskan.', 'users_count' => 96, 'permissions_count' => 8],
        ];
    }

    public static function modules(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard'],
            ['key' => 'users', 'label' => 'Kelola Pengguna'],
            ['key' => 'roles', 'label' => 'Kelola Peranan'],
            ['key' => 'settings', 'label' => 'Kelola Aplikasi'],
            ['key' => 'security', 'label' => 'Kelola Keamanan'],
            ['key' => 'database', 'label' => 'Kelola Database'],
            ['key' => 'activity', 'label' => 'Log Aktivitas'],
        ];
    }

    public static function matrix(): array
    {
        return [
            'administrator' => ['dashboard', 'users', 'roles', 'settings', 'security', 'database', 'activity'],
            'editor' => ['dashboard', 'users', 'activity'],
            'auditor' => ['dashboard', 'activity', 'database'],
            'staf' => ['dashboard'],
        ];
    }

    public static function logs(): array
    {
        $rows = [
            ['17 Jun 2026, 09.42', 'Budi Santoso', 'Memperbarui peranan Editor', 'Peranan', '10.14.2.31', 'Info', '--st-progress'],
            ['17 Jun 2026, 09.15', 'Dimas Prakoso', 'Menambah pengguna Rani Kusuma', 'Pengguna', '10.14.2.10', 'Info', '--st-progress'],
            ['17 Jun 2026, 08.58', 'Dimas Prakoso', 'Mengunggah logo aplikasi', 'Aplikasi', '10.14.2.10', 'Info', '--st-progress'],
            ['17 Jun 2026, 08.30', 'agus.wibowo', 'Gagal masuk 3 kali berturut-turut', 'Keamanan', '103.28.14.7', 'Peringatan', '--st-pending'],
            ['17 Jun 2026, 07.55', 'Sistem', 'Cadangan basis data selesai', 'Database', '127.0.0.1', 'Sukses', '--st-done'],
            ['17 Jun 2026, 07.20', 'Sistem', 'Menghapus 12 arsip kedaluwarsa', 'Arsip', '127.0.0.1', 'Info', '--st-progress'],
            ['16 Jun 2026, 22.06', 'Sistem', 'Antrean email gagal terkirim', 'Notifikasi', '127.0.0.1', 'Gagal', '--st-overdue'],
            ['16 Jun 2026, 17.41', 'Maya Anggraini', 'Mengekspor daftar pengguna', 'Pengguna', '10.14.2.44', 'Info', '--st-progress'],
            ['16 Jun 2026, 16.55', 'Dimas Prakoso', 'Mengubah kebijakan kata sandi', 'Keamanan', '10.14.2.10', 'Peringatan', '--st-pending'],
            ['16 Jun 2026, 13.27', 'Dewi Puspita', 'Memperbarui profil sendiri', 'Profil', '10.14.2.58', 'Info', '--st-progress'],
            ['15 Jun 2026, 09.02', 'Hendra Saputra', 'Masuk ke sistem', 'Keamanan', '10.14.2.61', 'Sukses', '--st-done'],
            ['14 Jun 2026, 15.40', 'Lia Marlina', 'Mengunduh laporan audit', 'Audit', '10.14.2.72', 'Info', '--st-progress'],
        ];

        return collect($rows)->map(fn ($row, $i) => [
            'id' => $i + 1,
            'created_at' => $row[0],
            'actor' => $row[1],
            'action' => $row[2],
            'module' => $row[3],
            'ip' => $row[4],
            'level_label' => $row[5],
            'level_chip' => $row[6],
        ])->all();
    }
}
