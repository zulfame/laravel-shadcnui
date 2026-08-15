<script setup>
import { computed } from 'vue';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

/**
 * Dua mode:
 * - Semantik (tanpa `color`): variant default | secondary | destructive | muted | outline.
 * - Palet (dengan `color`): 14 warna × gaya solid | light | outline.
 */
const props = defineProps({
    variant: { type: String, default: 'default' },
    color: { type: String, default: null },
    class: { type: null, default: '' },
});

const badgeVariants = cva(
    'inline-flex items-center gap-1 rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors [&>svg]:size-3 [&>svg]:shrink-0',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground shadow',
                secondary: 'border-transparent bg-foreground/85 text-background shadow-sm dark:bg-foreground/90',
                destructive: 'border-transparent bg-destructive text-destructive-foreground shadow',
                muted: 'border-transparent bg-secondary text-secondary-foreground',
                outline: 'text-foreground',
            },
        },
        defaultVariants: { variant: 'default' },
    },
);

const styleMap = { solid: 'bdg-solid', light: 'bdg-light', outline: 'bdg-outline' };
// Kelas literal (bukan string dinamis) agar tidak dibuang saat purge Tailwind.
const neutralInkMap = { light: 'bdg-c-light', dark: 'bdg-c-dark' };

const isPalette = computed(() => Boolean(props.color) && props.color !== 'default');

const classes = computed(() => {
    if (!props.color) {
        return cn(badgeVariants({ variant: props.variant }), props.class);
    }

    const base = badgeVariants({ variant: 'muted' }).replace(
        'border-transparent bg-secondary text-secondary-foreground',
        '',
    );

    if (!isPalette.value) {
        return cn(base, 'border-transparent bg-transparent text-muted-foreground', props.class);
    }

    const style = styleMap[props.variant] ?? styleMap.solid;

    return cn(base, style, neutralInkMap[props.color], props.class);
});

const inlineStyle = computed(() =>
    isPalette.value ? { '--bdg': `var(--bdg-${props.color})` } : undefined,
);
</script>

<template>
    <div :class="classes" :style="inlineStyle">
        <slot />
    </div>
</template>
