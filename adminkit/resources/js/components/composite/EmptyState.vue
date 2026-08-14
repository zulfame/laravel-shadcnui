<script setup>
import { computed } from 'vue';
import { AlertTriangle, FileText, Lock, SearchX, Sparkles, WifiOff } from 'lucide-vue-next';

const VARIANTS = {
    'no-data': { icon: FileText, title: 'Belum ada data', description: 'Belum ada apa pun di sini.' },
    'no-results': { icon: SearchX, title: 'Tidak ada hasil', description: 'Tidak ada item yang cocok dengan pencarian atau filter.' },
    'first-time': { icon: Sparkles, title: 'Mulai dari sini', description: 'Buat item pertama Anda untuk memulai.' },
    forbidden: { icon: Lock, title: 'Akses ditolak', description: 'Anda tidak memiliki izin untuk melihat konten ini.' },
    offline: { icon: WifiOff, title: 'Anda sedang offline', description: 'Periksa koneksi lalu coba lagi.' },
    error: { icon: AlertTriangle, title: 'Terjadi kesalahan', description: 'Konten tidak dapat dimuat. Silakan coba lagi.' },
};

const props = defineProps({
    variant: { type: String, default: 'no-data' },
    icon: { type: [Object, Function], default: null },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    testid: { type: String, default: undefined },
});

const preset = computed(() => VARIANTS[props.variant] ?? VARIANTS['no-data']);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center"
        :data-testid="props.testid ?? `empty-state-${props.variant}`"
    >
        <span class="flex size-10 items-center justify-center rounded-lg border bg-muted/40 text-muted-foreground">
            <component :is="props.icon ?? preset.icon" class="size-5" aria-hidden="true" />
        </span>
        <div class="space-y-1">
            <p class="font-medium">{{ props.title || preset.title }}</p>
            <p class="text-xs text-muted-foreground">{{ props.description || preset.description }}</p>
        </div>
        <slot name="action" />
    </div>
</template>
