<script setup>
import { router } from '@inertiajs/vue3';
import { WifiOff } from 'lucide-vue-next';

import Button from '@/components/ui/Button.vue';
import { useNetworkStatus } from '@/composables/useNetworkStatus';

const { online } = useNetworkStatus();

const retry = () => router.reload({ preserveScroll: true });
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-200"
        enter-from-class="-translate-y-full opacity-0"
        leave-active-class="transition-all duration-200"
        leave-to-class="-translate-y-full opacity-0"
    >
        <div
            v-if="!online"
            class="flex shrink-0 items-center gap-2 border-b border-destructive/40 bg-destructive/10 px-4 py-2 text-xs text-destructive"
            role="status"
            data-testid="offline-banner"
        >
            <WifiOff class="size-3.5 shrink-0" aria-hidden="true" />
            <span class="min-w-0 flex-1">
                Anda sedang offline — data yang tampil mungkin tidak terbaru dan perubahan tidak akan tersimpan.
            </span>
            <Button
                variant="outline"
                size="sm"
                class="h-6 border-destructive/40 px-2 text-xs text-destructive hover:bg-destructive/10"
                data-testid="offline-retry"
                @click="retry"
            >
                Coba Lagi
            </Button>
        </div>
    </Transition>
</template>
