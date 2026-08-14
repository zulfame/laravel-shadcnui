<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({
    orientation: { type: String, default: 'horizontal' },
    class: { type: null, default: '' },
});
</script>

<template>
    <div
        role="separator"
        :class="
            cn(
                'shrink-0 bg-border',
                props.orientation === 'vertical' ? 'h-full w-px' : 'h-px w-full',
                props.class,
            )
        "
    />
</template>
