<script setup>
import { computed } from 'vue';

/**
 * MiniBarChart — grafik batang SVG bertoken (tanpa dependensi grafik).
 * `series`: [{ key, label, token }] · `data`: [{ label, [key]: number }]
 * Grafik DIKECUALIKAN dari aturan monokrom demi keterbacaan, tetapi warna
 * tetap token-only (`hsl(var(--token))`).
 */
const props = defineProps({
    data: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    height: { type: Number, default: 220 },
});

const max = computed(() => {
    const values = props.data.flatMap((d) => props.series.map((s) => Number(d[s.key]) || 0));
    return Math.max(1, ...values);
});

const plotHeight = computed(() => props.height - 32);
const barHeight = (value) => Math.max(2, ((Number(value) || 0) / max.value) * plotHeight.value);
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-end gap-3" :style="{ height: `${props.height}px` }">
            <div class="flex w-6 shrink-0 flex-col justify-between self-stretch pb-6 text-right text-[11px] tabular-nums text-muted-foreground">
                <span>{{ max }}</span>
                <span>0</span>
            </div>
            <div class="flex flex-1 items-end justify-between gap-2 border-l border-b border-border pl-2">
                <div
                    v-for="point in props.data"
                    :key="point.label"
                    class="flex min-w-0 flex-1 flex-col items-center gap-1.5"
                >
                    <div class="flex h-full w-full items-end justify-center gap-1" :style="{ height: `${plotHeight}px` }">
                        <div
                            v-for="s in props.series"
                            :key="s.key"
                            class="w-full max-w-[14px] rounded-t-[3px] transition-[height] duration-300"
                            :style="{ height: `${barHeight(point[s.key])}px`, backgroundColor: `hsl(var(${s.token}))` }"
                            :title="`${s.label}: ${point[s.key]}`"
                        />
                    </div>
                    <span class="truncate text-[11px] text-muted-foreground">{{ point.label }}</span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-4 text-[11px] text-muted-foreground">
            <span v-for="s in props.series" :key="s.key" class="flex items-center gap-1.5">
                <span class="size-2 rounded-sm" :style="{ backgroundColor: `hsl(var(${s.token}))` }" />
                {{ s.label }}
            </span>
        </div>
    </div>
</template>
