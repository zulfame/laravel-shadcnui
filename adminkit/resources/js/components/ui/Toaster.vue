<script setup>
import { AlertCircle, CheckCircle2, Info, TriangleAlert, X } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();

// Aksen kiri + warna ikon memakai token; badan toast tetap monokrom.
const META = {
    success: { icon: CheckCircle2, accent: '--success' },
    error: { icon: AlertCircle, accent: '--destructive' },
    warning: { icon: TriangleAlert, accent: '--warning' },
    info: { icon: Info, accent: '--foreground' },
};
</script>

<template>
    <div
        class="pointer-events-none fixed bottom-4 right-4 z-[100] flex w-[22rem] max-w-[calc(100vw-2rem)] flex-col gap-2"
        role="region"
        aria-label="Notifikasi"
    >
        <div
            v-for="t in toasts"
            :key="t.id"
            class="pointer-events-auto flex items-start gap-3 rounded-lg border border-l-4 bg-card px-3 py-2.5 text-card-foreground shadow-lg animate-in fade-in slide-in-from-bottom-2"
            :style="{ borderLeftColor: `hsl(var(${META[t.variant].accent}))` }"
            :data-testid="`toast-${t.variant}`"
        >
            <component
                :is="META[t.variant].icon"
                class="mt-0.5 size-4 shrink-0"
                :style="{ color: `hsl(var(${META[t.variant].accent}))` }"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-semibold">{{ t.title }}</p>
                <p class="text-xs text-muted-foreground">{{ t.description }}</p>
            </div>
            <button
                type="button"
                class="rounded-md p-1 text-muted-foreground transition-colors hover:text-foreground"
                aria-label="Tutup"
                @click="dismiss(t.id)"
            >
                <X class="size-3.5" />
            </button>
        </div>
    </div>
</template>
