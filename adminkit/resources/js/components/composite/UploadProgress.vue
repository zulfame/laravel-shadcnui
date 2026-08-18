<script setup>
import { computed } from 'vue';
import { Loader2 } from 'lucide-vue-next';

import Progress from '@/components/ui/Progress.vue';

/** Bilah progres unggahan Inertia (`form.progress`). */
const props = defineProps({
    progress: { type: Object, default: null },
    label: { type: String, default: 'Mengunggah berkas' },
    testid: { type: String, default: 'upload-progress' },
});

const percentage = computed(() => props.progress?.percentage ?? 0);

const size = computed(() => {
    const loaded = props.progress?.loaded ?? 0;
    const total = props.progress?.total ?? 0;
    const mb = (value) => `${(value / 1048576).toFixed(1).replace('.', ',')} MB`;

    return total ? `${mb(loaded)} / ${mb(total)}` : '';
});
</script>

<template>
    <div v-if="progress" class="space-y-1.5" role="status" :data-testid="testid">
        <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-muted-foreground">
                <Loader2 class="size-3 animate-spin" aria-hidden="true" />
                {{ percentage < 100 ? label : 'Menyelesaikan…' }}
            </span>
            <span class="tabular-nums" :data-testid="`${testid}-value`">
                {{ size ? `${size} · ` : '' }}{{ percentage }}%
            </span>
        </div>
        <Progress :value="percentage" class="h-1.5" />
    </div>
</template>
