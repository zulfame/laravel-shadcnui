<script setup>
import { DropdownMenuItem } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({
    asChild: { type: Boolean, default: false },
    class: { type: null, default: '' },
});
</script>

<template>
    <DropdownMenuItem
        :as-child="props.asChild"
        :class="
            cn(
                'relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground [&>svg]:size-4 [&>svg]:shrink-0',
                props.class,
            )
        "
    >
        <slot />
    </DropdownMenuItem>
</template>
