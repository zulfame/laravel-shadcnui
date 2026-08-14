# AdminKit — Starter Kit Panel Admin

Laravel 12 · PHP 8.2 · Inertia.js · Vue 3 · TailwindCSS 3 · Vite 6 · SQLite

Design system mengikuti **FlowDesk**: compact (satu kerapatan, tidak dapat diubah),
monokrom, token-first, ikon `lucide-vue-next` saja, bahasa antarmuka Bahasa Indonesia.

## Status

**FASE STATIS** — hanya menu **Dashboard** yang aktif. Seluruh angka/daftar masih
data contoh dari `app/Support/DemoData.php`. Autentikasi, basis data, dan menu
lain dipasang setelah tampilan disetujui.

## Menjalankan

```bash
composer install
yarn install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate

# terminal 1
php artisan serve
# terminal 2
yarn dev
```

Build produksi: `yarn build`.

## Struktur

```
app/
├── Http/Middleware/HandleInertiaRequests.php   # data yang dibagikan ke semua halaman
└── Support/DemoData.php                        # data contoh fase statis
config/adminkit.php                             # branding + pengguna demo
resources/
├── css/app.css                                 # token desain 2 lapis + utilitas compact
└── js/
    ├── app.js
    ├── components/
    │   ├── ui/                                 # primitive (Button, Card, Table, Dialog, …)
    │   │   └── sidebar/                        # sistem sidebar (kuncup ke ikon)
    │   ├── composite/                          # DataTableCard, StateChip, EmptyState, grafik
    │   ├── layout/                             # AppLayout, AppSidebar, AuthLayout, NotificationsBell
    │   └── ModeToggle.vue
    ├── config/navigation.js                    # SATU-SATUNYA sumber menu & breadcrumb
    ├── constants/labels.js                     # leksikon label aksi (ACTION)
    └── pages/                                  # halaman Inertia
routes/web.php
```

## Aturan desain yang dikunci

| Kode | Aturan |
|---|---|
| Kerapatan | Compact permanen: `--ctl-h: 2rem`, `--ctl-h-sm: 1.75rem`, `--tbl-cell-py: 0.25rem`. Tidak ada pemilih kerapatan. |
| Tipografi | `body` 14px/1.45 · tabel & form 13px · meta 12px · judul card 16px. Halaman fitur dilarang `text-lg` ke atas. |
| Warna | Hanya `hsl(var(--token))`. Dilarang hex atau kelas warna Tailwind mentah. UI monokrom; warna hanya untuk badge status (`--st-*`, `--pr-*`) dan grafik. |
| Card | Header & footer `bg-sidebar` + `px-6 py-3`; badan `px-6 py-4`. Jangan ditimpa per halaman. |
| Tombol | Wajib berikon lucide di kiri label, termasuk Batal/Tutup (`X`). Teks memakai `ACTION` dari `constants/labels.js`. |
| Footer aksi | Dua tombol → `justify-between` (sekunder kiri, utama kanan). Satu tombol → `justify-end`. |
| Header shell | Hanya SidebarTrigger + breadcrumb + (kanan) lonceng notifikasi & ModeToggle. |
| Sidebar | Berbasis AREA (Member Area / Administrator) dari `config/navigation.js`. Area tanpa menu menampilkan catatan netral. |
| Pencarian | Placeholder seragam `"Pencarian..."` (`ACTION.search`). |
| Kartu daftar | Tinggi terkunci + isi bergulir (`min-h-0 flex-1 p-0` + `overflow-y-auto`), bukan kartu memanjang. |
| Test id | Setiap elemen interaktif & informasi penting punya `data-testid` kebab-case. |
