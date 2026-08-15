/**
 * Cermin aturan backend (`app/Support/Rules.php`) untuk validasi cepat di UI.
 * Setiap validator mengembalikan pesan error (string) atau '' bila valid.
 */
const PHONE = /^\+?[0-9]{9,15}$/;
const PERSON_NAME = /^[\p{L}\p{M} .'-]+$/u;
const USERNAME = /^[a-z0-9_-]+$/;
const EMAIL = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/;
const URL_HTTP = /^https?:\/\/[^\s]+$/i;
const SLUG = /^[a-z0-9.-]+$/;
const PATH = /^[A-Za-z0-9_\-/]+$/;

const isBlank = (v) => v === null || v === undefined || String(v).trim() === '';

export const required = (label) => (v) => (isBlank(v) ? `Kolom ${label} wajib diisi.` : '');

export const min = (n, label) => (v) =>
    !isBlank(v) && String(v).trim().length < n ? `${label} minimal ${n} karakter.` : '';

export const max = (n, label) => (v) =>
    !isBlank(v) && String(v).length > n ? `${label} maksimal ${n} karakter.` : '';

export const personName = (label) => (v) =>
    !isBlank(v) && !PERSON_NAME.test(v)
        ? `${label} hanya boleh huruf, spasi, titik, apostrof, dan tanda hubung.`
        : '';

export const username = (label) => (v) =>
    !isBlank(v) && !USERNAME.test(v)
        ? `${label} hanya boleh huruf kecil, angka, garis bawah, dan tanda hubung.`
        : '';

export const email = (label) => (v) => (!isBlank(v) && !EMAIL.test(v) ? `${label} tidak valid.` : '');

export const phone = (label) => (v) =>
    !isBlank(v) && !PHONE.test(v)
        ? `${label} hanya boleh angka (9–15 digit) dan boleh diawali tanda +.`
        : '';

export const url = (label) => (v) =>
    !isBlank(v) && !URL_HTTP.test(v) ? `${label} harus berupa URL lengkap (http/https).` : '';

export const slug = (label) => (v) =>
    !isBlank(v) && !SLUG.test(v) ? `${label} hanya boleh huruf kecil, angka, titik, dan tanda hubung.` : '';

export const path = (label) => (v) =>
    !isBlank(v) && !PATH.test(v)
        ? `${label} hanya boleh huruf, angka, garis bawah, tanda hubung, dan garis miring.`
        : '';

export const sameAs = (field, label) => (v, form) =>
    !isBlank(v) && v !== form[field] ? `${label} tidak cocok.` : '';

/** Gabungkan beberapa validator; pesan pertama yang gagal dipakai. */
export const all =
    (...fns) =>
    (v, form) => {
        for (const fn of fns) {
            const message = fn(v, form);
            if (message) return message;
        }
        return '';
    };
