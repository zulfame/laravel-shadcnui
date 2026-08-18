import { onBeforeUnmount, onMounted, readonly, ref } from 'vue';
import { router } from '@inertiajs/vue3';

import { notify } from '@/composables/useToast';

const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine);

/**
 * Status koneksi jaringan.
 * Sumber: event `online`/`offline` peramban + kegagalan permintaan Inertia
 * (mencakup kasus "terhubung WiFi tapi internet mati").
 */
export function useNetworkStatus() {
    const setOnline = (value) => {
        if (online.value === value) return;

        online.value = value;

        if (value) {
            notify.success('Koneksi kembali normal.');
        } else {
            notify.error('Koneksi terputus. Perubahan tidak akan tersimpan.');
        }
    };

    const goOnline = () => setOnline(true);
    const goOffline = () => setOnline(false);
    let stopInertia = () => {};

    onMounted(() => {
        window.addEventListener('online', goOnline);
        window.addEventListener('offline', goOffline);

        const offError = router.on('exception', () => {
            if (!navigator.onLine) goOffline();
        });
        const offSuccess = router.on('success', goOnline);

        stopInertia = () => {
            offError();
            offSuccess();
        };
    });

    onBeforeUnmount(() => {
        window.removeEventListener('online', goOnline);
        window.removeEventListener('offline', goOffline);
        stopInertia();
    });

    return { online: readonly(online) };
}
