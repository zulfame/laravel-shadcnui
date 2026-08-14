<script setup>
import { computed } from 'vue';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import Tooltip from '@/components/ui/Tooltip.vue';
import TooltipTrigger from '@/components/ui/TooltipTrigger.vue';
import TooltipContent from '@/components/ui/TooltipContent.vue';
import { useSidebar } from './context';

const props = defineProps({
    as: { type: [String, Object], default: 'button' },
    size: { type: String, default: 'default' },
    isActive: { type: Boolean, default: false },
    tooltip: { type: String, default: '' },
    class: { type: null, default: '' },
});

const { state, isMobile } = useSidebar();

const variants = cva(
    'peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm outline-none transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground data-[state=open]:hover:bg-sidebar-accent group-data-[collapsible=icon]:!size-8 group-data-[collapsible=icon]:!p-2 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0',
    {
        variants: {
            size: {
                default: 'h-8 text-sm',
                sm: 'h-7 text-xs',
                lg: 'h-12 text-sm group-data-[collapsible=icon]:!p-0',
            },
        },
        defaultVariants: { size: 'default' },
    },
);

const classes = computed(() => cn(variants({ size: props.size }), props.class));
const showTooltip = computed(() => Boolean(props.tooltip) && state.value === 'collapsed' && !isMobile.value);
</script>

<template>
    <Tooltip v-if="showTooltip">
        <TooltipTrigger>
            <component :is="props.as" :class="classes" :data-active="props.isActive">
                <slot />
            </component>
        </TooltipTrigger>
        <TooltipContent side="right">{{ props.tooltip }}</TooltipContent>
    </Tooltip>
    <component v-else :is="props.as" :class="classes" :data-active="props.isActive">
        <slot />
    </component>
</template>
