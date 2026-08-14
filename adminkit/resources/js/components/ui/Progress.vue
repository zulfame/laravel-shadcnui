<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({
    value: { type: Number, default: 0 },
    class: { type: null, default: '' },
});
</script>

<template>
    <div
        role="progressbar"
        :aria-valuenow="props.value"
        :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-muted', props.class)"
    >
        <div
            class="h-full bg-primary transition-transform"
            :style="{ width: `${Math.min(100, Math.max(0, props.value))}%` }"
        />
    </div>
</template>
