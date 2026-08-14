import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { notify } from '@/composables/useToast';

/** Ubah flash message dari server menjadi toast. */
export function useFlashToast() {
    const page = usePage();

    const emit = (flash) => {
        if (flash?.success) notify.success(flash.success);
        if (flash?.error) notify.error(flash.error);
    };

    emit(page.props.flash);
    watch(() => page.props.flash, emit);
}
