import { usePage } from '@inertiajs/vue3';

/** Cari label menu (modul Menu Sidebar) berdasarkan alamat halaman. */
const findLabel = (items, href) => {
    for (const item of items ?? []) {
        if (item.href === href) return item.label;

        const nested = findLabel(item.children, href);
        if (nested) return nested;
    }

    return null;
};

/**
 * Label resmi sebuah halaman: mengikuti nama menu yang diatur pengguna di
 * modul Menu Sidebar, jatuh ke `fallback` bila menu tersebut tidak ada.
 */
export const menuLabelOf = (href, fallback = '') => {
    const areas = usePage().props.menu ?? [];

    for (const area of areas) {
        const label = findLabel(area.items, href);
        if (label) return label;
    }

    return fallback;
};
