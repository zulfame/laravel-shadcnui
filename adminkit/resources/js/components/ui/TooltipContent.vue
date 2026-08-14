<script setup>
import { TooltipContent, TooltipPortal } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({
    side: { type: String, default: 'right' },
    sideOffset: { type: Number, default: 6 },
    hidden: { type: Boolean, default: false },
    class: { type: null, default: '' },
});
</script>

<template>
    <TooltipPortal v-if="!props.hidden">
        <TooltipContent
            :side="props.side"
            :side-offset="props.sideOffset"
            :class="
                cn(
                    'z-50 overflow-hidden rounded-md bg-primary px-2 py-1 text-xs text-primary-foreground',
                    props.class,
                )
            "
        >
            <slot />
        </TooltipContent>
    </TooltipPortal>
</template>
