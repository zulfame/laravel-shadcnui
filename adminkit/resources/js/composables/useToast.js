import { ref } from 'vue';

/**
 * Toast terpusat. Judul BAKU (tidak boleh dikarang):
 * success → "Sukses" · error → "Gagal" · warning → "Peringatan" · info → "Info".
 * Pemanggil hanya mengisi deskripsi, mis. notify.success('Profil diperbarui.').
 */
const TITLES = {
    success: 'Sukses',
    error: 'Gagal',
    warning: 'Peringatan',
    info: 'Info',
};

const toasts = ref([]);
let seq = 0;

function push(variant, description, duration = 4000) {
    if (!description) return;
    const id = ++seq;
    toasts.value.push({ id, variant, title: TITLES[variant], description });
    window.setTimeout(() => dismiss(id), duration);
}

function dismiss(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
}

export const notify = {
    success: (description) => push('success', description),
    error: (description) => push('error', description),
    warning: (description) => push('warning', description),
    info: (description) => push('info', description),
};

export function useToast() {
    return { toasts, notify, dismiss };
}
