<script setup>
import { DropdownMenuContent, DropdownMenuPortal } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({
    align: { type: String, default: 'start' },
    side: { type: String, default: 'bottom' },
    sideOffset: { type: Number, default: 4 },
    class: { type: null, default: '' },
});
</script>

<template>
    <DropdownMenuPortal>
        <DropdownMenuContent
            :align="props.align"
            :side="props.side"
            :side-offset="props.sideOffset"
            :class="
                cn(
                    'z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md',
                    'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
                    props.class,
                )
            "
        >
            <slot />
        </DropdownMenuContent>
    </DropdownMenuPortal>
</template>
