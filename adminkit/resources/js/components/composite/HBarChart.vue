<script setup>
import { computed } from 'vue';

/** HBarChart — batang horizontal bertoken untuk data kategori. */
const props = defineProps({
    data: { type: Array, default: () => [] }, // [{ label, count }]
    height: { type: Number, default: 220 },
});

const max = computed(() => Math.max(1, ...props.data.map((d) => Number(d.count) || 0)));
const width = (value) => `${((Number(value) || 0) / max.value) * 100}%`;
</script>

<template>
    <div class="flex flex-col justify-center gap-3" :style="{ height: `${props.height}px` }">
        <div v-for="(row, i) in props.data" :key="row.label" class="flex items-center gap-3">
            <span class="w-28 shrink-0 truncate text-right text-[11px] text-muted-foreground">{{ row.label }}</span>
            <div class="flex h-3.5 flex-1 items-center">
                <div
                    class="h-full rounded-r-[3px] transition-[width] duration-300"
                    :style="{ width: width(row.count), backgroundColor: `hsl(var(--chart-${(i % 5) + 1}))` }"
                />
            </div>
            <span class="w-8 shrink-0 text-right text-[11px] tabular-nums text-muted-foreground">{{ row.count }}</span>
        </div>
    </div>
</template>
