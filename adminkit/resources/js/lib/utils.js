import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

/** Inisial avatar dari nama atau email. */
export function initialsOf(name, email) {
    return ((name || email || 'U').trim().slice(0, 1) || 'U').toUpperCase();
}

/** Format tanggal/waktu baku untuk sel tabel (locale id-ID). */
export function fmtDate(iso) {
    if (!iso) return '\u2014';
    const d = new Date(iso);
    return Number.isNaN(d.getTime())
        ? '\u2014'
        : d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}
