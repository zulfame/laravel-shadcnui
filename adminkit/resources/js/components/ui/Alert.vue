<script setup>
import { computed } from 'vue';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: { type: String, default: 'default' },
    class: { type: null, default: '' },
});

// Layout grid (ikon | konten) supaya ikon selalu sejajar dengan baris teks pertama.
const alertVariants = cva(
    'relative grid w-full grid-cols-[1rem_1fr] items-start gap-x-3 gap-y-1 rounded-lg border px-4 py-3 text-sm [&>svg]:size-4 [&>svg]:translate-y-[2px] [&>svg]:text-current',
    {
        variants: {
            variant: {
                default: 'bg-background text-foreground',
                destructive: 'border-destructive/50 text-destructive dark:border-destructive',
            },
        },
        defaultVariants: { variant: 'default' },
    },
);

const classes = computed(() => cn(alertVariants({ variant: props.variant }), props.class));
</script>

<template>
    <div role="alert" :class="classes">
        <slot />
    </div>
</template>
